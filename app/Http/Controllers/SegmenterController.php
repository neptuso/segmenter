<?php

namespace App\Http\Controllers;

use App\Model\Archivo;
use Illuminate\Http\Request;
use Auth;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use App\MyDB;
use App\Listado;
use App\Imports\CsvImport;
use Maatwebsite\Excel;
use App\Model\Aglomerado;
use App\Model\Provincia;
use App\Model\Departamento;
use App\Model\Localidad;
use App\Model\Radio;
use App\Model\Fraccion;
use App\Model\TipoRadio;
use App\Exceptions\GeoestadisticaException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SegmenterController extends Controller
{
    private $epsgs=[];

    public function __construct()
    {
        $segmenta_auto=false;
        $this->middleware('auth');
        $this->epsgs['epsg:22182']='(EPSG:22182) POSGAR 94/Argentina 2 - San Juan, Mendoza, Neuquén, Chubut, Santa Cruz y Tierra del Fuego...';
        $this->epsgs['epsg:22183']='(EPSG:22183) POSGAR 94/Argentina 3 - Jujuy, Salta, Tucuman, Catamarca, La Rioja, San Luis, La Pampa y Río Negro';
        $this->epsgs['epsg:22184']='(EPSG:22184) POSGAR 94/Argentina 4 - Santiago del Estero y Córdoba';
        $this->epsgs['epsg:22185']='(EPSG:22185) POSGAR 94/Argentina 5 - Formosa, Chaco, Santa Fe, Entre Ríos y Buenos Aires';
        $this->epsgs['epsg:22186']='(EPSG:22186) POSGAR 94/Argentina 6 - Corrientes';
        $this->epsgs['epsg:22187']='(EPSG:22187) POSGAR 94/Argentina 7 - Misiones';
        $this->epsgs['sr-org:8333']='(SR-ORG:8333) Gauss Krugger BA - Ciudad Autónoma de Buenos Aires';
    }

    public function index()
    {
        $data=null;
        return view('segmenter/index',['data' => $data,'epsgs'=> $this->epsgs]);
    }

    public function store(Request $request)
    {
    if (! Auth::check()) {
        $mensaje='No tiene permiso para segmentar o no esta logueado';
        flash($mensaje)->error()->important();
        return $mensaje;
    }else{
      $AppUser= Auth::user();
      $data = [];
      $segmenta_auto=false;
      $epsg_id = $request->input('epsg_id')?$request->input('epsg_id'):'epsg:22183';
      $data['epsg']['id']=$epsg_id;
      flash('SRS: '.$data['epsg']['id']);

    if ($request->hasFile('c1')) {
     if($c1_file = Archivo::cargar($request->c1, Auth::user())) {
         flash("Archivo C1 ")->info();
     } else {
         flash("Error en el modelo cargar archivo")->error();
     }
     $c1_file->procesar();
     if (!$c1_file->procesado) {
            flash($data['file']['error']='Archivo '.$c1_file->nombre_original.' sin Procesar por error')->important();
     }else{
            $codaglo=$c1_file->moverData();
     }
    }

    if ($epsg_id=='sr-org:8333'){
            // Log::debug('Proyeccion de CABA en '.$codaglo.', con SRID: '.$epsg_id);
            // USO .prj 8333.prj
            $prj_file='./app/developer_docs/8333.prj';
            $epsg_def= $epsg_id;
            $epsg_def='+proj=tmerc +lat_0=-34.6297166 +lon_0=-58.4627 +k=1 +x_0=100000 +y_0=100000 +ellps=intl +units=m +no_defs';
            $srs_name='sr-org:8333';
            $segmenta_auto=true;
    }else {
            $epsg_def= '';
    }
    $codaglo=isset($codaglo)?$codaglo:'temporal';
    $processOGR2OGR =
                Process::fromShellCommandline('(/usr/bin/ogr2ogr -f \
                "PostgreSQL" PG:"dbname=$db host=$host user=$user port=$port \
                active_schema=e$e00 password=$pass" --config PG_USE_COPY YES \
                -lco OVERWRITE=YES --config OGR_TRUNCATE YES -dsco \
                PRELUDE_STATEMENTS="SET client_encoding TO latin1;CREATE SCHEMA \
                IF NOT EXISTS e$e00;" -dsco active_schema=e$e00 -lco \
                PRECISION=NO -lco SCHEMA=e$e00 \
                -nln $capa \
                -skipfailures \
                -overwrite $file )');
    $processOGR2OGR->setTimeout(3600);
    if ($request->hasFile('shp_lab')) {
      $original_name = $request->shp_lab->getClientOriginalName();
      $original_extension = strtolower($request->shp_lab->getClientOriginalExtension());
        if ($original_extension == 'shp'){
            $random_name='t_'.$request->shp_lab->hashName();
            $data['file']['shp_lab'] = $request->shp_lab->storeAs('segmentador', $random_name.'.shp');
            if ($request->hasFile('shx_lab')) {
                $data['file']['shx_lab'] = $request->shx_lab->storeAs('segmentador', $random_name.'.shx');
            }
            if ($request->hasFile('prj_lab')) {
                $data['file']['prj_lab'] = $request->prj_lab->storeAs('segmentador', $random_name.'.prj');
            }
            if ($request->hasFile('dbf_lab')) {
                $data['file']['dbf_lab'] = $request->dbf_lab->storeAs('segmentador', $random_name.'.dbf');
            }

           //Cargo etiquetas
           $processOGR2OGR->run(null, ['capa'=>'lab','epsg'=>$epsg_def,
               'file' => storage_path().'/app/'.$data['file']['shp_lab'],
               'e00'=>$codaglo[0]->link,
               'db'=>Config::get('database.connections.pgsql.database'),
               'host'=>Config::get('database.connections.pgsql.host'),
               'user'=>Config::get('database.connections.pgsql.username'),
               'pass'=>Config::get('database.connections.pgsql.password'),
               'port'=>Config::get('database.connections.pgsql.port')]);
        }
    }
    if ($request->hasFile('shp')) {
     if($shp_file = Archivo::cargar($request->shp, Auth::user())) {
       flash("Archivo Shp/E00 ")->info();
     } else {
         flash("Error en el modelo cargar archivo al procesar SHP/E00")->error();
     }
     if ($request->file('shp')->isValid() or true) {
            $data['file']['shp_msg'] = "Subió una base geográfica ";
            $original_name = $request->shp->getClientOriginalName();
            $data['file']['shp_msg'] .= " y nombre original: ".$original_name;
            $original_extension = strtolower($request->shp->getClientOriginalExtension());
            $data['file']['shp_msg'] .= ". Extension original: ".$original_extension;
            flash($data['file']['shp_msg']);

        if ($original_extension == 'shp'){
            $random_name='t_'.$request->shp->hashName();
            $data['file']['shp'] = $request->shp->storeAs('segmentador', $random_name.'.'.$request->shp->getClientOriginalExtension());
            if ($request->hasFile('shx')) {
                $data['file']['shx'] = $request->shx->storeAs('segmentador', $random_name.'.'.$request->shx->getClientOriginalExtension());
            }
            if ($request->hasFile('prj')) {
                $data['file']['prj'] = $request->prj->storeAs('segmentador', $random_name.'.'.$request->prj->getClientOriginalExtension());
            }
            if ($request->hasFile('dbf')) {
                $data['file']['dbf'] = $request->dbf->storeAs('segmentador', $random_name.'.'.$request->dbf->getClientOriginalExtension());
            }

            $process = Process::fromShellCommandline('echo "$tiempo: $usuario_name ($usuario_id) -> $log" >> archivos.log');
            $process->run(null, ['log' => "Archivo: ".$original_name." subido como: ".$data['file']['shp'],
                                 'usuario_id' => $AppUser->id,
                                 'usuario_name' => $AppUser->name,
                                 'tiempo' => date('Y-m-d H:i:s')]);
             
            if ($epsg_id=='sr-org:8333'){
                 //Cargo arcos
                $processOGR2OGR->run(null, ['capa'=>'arc',
                     'epsg'=>$epsg_def,'file' => storage_path().'/app/'.$data['file']['shp'],
                     'e00'=>$codaglo[0]->link,
                     'db'=>Config::get('database.connections.pgsql.database'),
                     'host'=>Config::get('database.connections.pgsql.host'),
                     'user'=>Config::get('database.connections.pgsql.username'),
                     'pass'=>Config::get('database.connections.pgsql.password'),
                     'port'=>Config::get('database.connections.pgsql.port')]);
            }else{
                $shp_file->epsg_def = $epsg_id;
                if( $ppddllls=$shp_file->procesar() ) {
                      flash('Proceso');
                }else{
                      flash('la pifio')->error();
                }
            }
            if (!$processOGR2OGR->isSuccessful()) {
                $epsg_def=isset($epsg_def)?$epsg_def:'No definido';
                dd($processOGR2OGR,'epsg '.$epsg_id,'epsg_def '.$epsg_def.
                'file '.storage_path().'/app/'.$data['file']['shp'],'e00 '.$codaglo[0]->link);
                throw new ProcessFailedException($processOGR2OGR);
            }
            MyDB::agregarsegisegd($codaglo[0]->link);

        }elseif ($original_extension == 'e00'){
          // PROCESAMIENTO PARA ARCHIVOS e00
          $shp_file->epsg_def = $epsg_id;
          $shp_file->save();
    if( $mensajes=$shp_file->procesar() ) {
      flash('Procesó e00')->important()->success();
      $ppdddllls=$shp_file->pasarData();
    }else{flash('No se pudo procesar la cartografía')->error()->important();
      $mensajes='ERROR';
      $ppdddllls=[];
        }
      if ($epsg_id=='sr-org:8333'){ // Si es CABA cargo sin epsg
            $processOGR2OGR = Process::fromShellCommandline('/usr/bin/ogr2ogr -f "PostgreSQL" PG:"dbname=$db host=$host user=$user port=$port active_schema=e$e00 password=$pass port=$port" --config PG_USE_COPY YES -lco OVERWRITE=YES --config OGR_TRUNCATE YES -dsco PRELUDE_STATEMENTS="SET client_encoding TO latin1;CREATE SCHEMA IF NOT EXISTS e$e00;" -dsco active_schema=e$e00 -lco PRECISION=NO -lco SCHEMA=e$e00 -skipfailures -addfields -overwrite $file ARC');
            $processOGR2OGR->setTimeout(3600);
            $processOGR2OGR->run(null, ['epsg' => $epsg_id, 'file' => storage_path().'/app/'.$data['file']['shp'],'e00'=>$codaglo[0]->link,'db'=>Config::get('database.connections.pgsql.database'),'host'=>Config::get('database.connections.pgsql.host'),'user'=>Config::get('database.connections.pgsql.username'),'pass'=>Config::get('database.connections.pgsql.password'),'port'=>Config::get('database.connections.pgsql.port')]);
         //    dd($processOGR2OGR);
        $processOGR2OGR_lab = Process::fromShellCommandline('/usr/bin/ogr2ogr -f "PostgreSQL" PG:"dbname=$db host=$host user=$user port=$port active_schema=e$e00 password=$pass" --config PG_USE_COPY YES -lco OVERWRITE=YES --config OGR_TRUNCATE YES -dsco PRELUDE_STATEMENTS="SET client_encoding TO latin1;CREATE SCHEMA IF NOT EXISTS e$e00;" -dsco active_schema=e$e00 -lco PRECISION=NO -lco SCHEMA=e$e00 -skipfailures -addfields -overwrite $file LAB');
        $processOGR2OGR_lab->setTimeout(3600);
        $processOGR2OGR_lab->run(null, ['epsg' => $epsg_id, 'file' => storage_path().'/app/'.$data['file']['shp'],'e00'=>$codaglo[0]->link,'db'=>Config::get('database.connections.pgsql.database'),'host'=>Config::get('database.connections.pgsql.host'),'user'=>Config::get('database.connections.pgsql.username'),'pass'=>Config::get('database.connections.pgsql.password'),'port'=>Config::get('database.connections.pgsql.port')]);
            //dd($processOGR2OGR_lab->getErrorOutput());
        flash($mensajes.=$data['file']['ogr2ogr_lab'] = $processOGR2OGR_lab->getErrorOutput().'<br />'.$processOGR2OGR_lab->getOutput())->important();
        flash($mensajes.=$data['file']['ogr2ogr'] = $processOGR2OGR->getErrorOutput().'<br />'.$processOGR2OGR->getOutput())->important();
        }
        if (!Str::contains($mensajes,['ERROR'])){
          flash('Se cargaron las Etiquetas y Arcos con éxito. ')->important()->success();
        }else{
          flash($mensajes)->important()->error();
        }
        foreach($ppdddllls as $ppdddlll){
          MyDB::agregarsegisegd($ppdddlll->link);
          MyDB::juntaListadoGeom('e'.$ppdddlll->link);
        }
        //MyDB::agregarsegisegd($codaglo);
        }else {//dd($request->file('shp'));
            flash('No se encontraron localidades')->error()->important();
        }
        if (isset($codaglo[0]->link)){
            if ($epsg_id=='sr-org:8333'){
               MyDB::setSRID('e'.$codaglo[0]->link,98333);
            }
        }
      }
    }
    if($segmenta_auto==true) {
          MyDB::segmentar_equilibrado($codaglo[0]->link,36);
          flash('Segmentado automáticamente a 36 viviendas x segmento')->important();
    }
    if ($request->hasFile('pxrad')) {
     if ($pxrad_file = Archivo::cargar($request->pxrad, Auth::user())) {
        $pxrad_file->tipo = 'pxrad/dbf';
        flash("Archivo PxRad ")->info();
        $procesar_result = $pxrad_file->procesar();
     } else {
         $procesar_result = false;
         flash("Error en el modelo cargar archivo")->error();
     }
      if ($procesar_result) 
      {
          $tabla = $pxrad_file->tabla;
            // Busco provincia encontrada en pxrad:
          try {
              $prov=MyDB::getCodProv($tabla,'public');
              if ($prov == 0) {
                 flash('Error grave. Buscando provincia. NO SE PUDO PROCESAR PXRAD ! ')->error()->important();
                 $data['file']['pxrad']='No se pudo procesar PxRad! ';
                 return view('segmenter/index', ['data' => $data,'epsgs'=> $this->epsgs]);
              }
              $oProvincia= Provincia::where('codigo', $prov)->first();
              if ($oProvincia==null){
                  $prov_data=MyDB::getDataProv($tabla,'public');
                  $oProvincia= new Provincia ($prov_data);
                  if ($oProvincia->save())
                  {
                      flash('Se creó la provincia: '.
                         $oProvincia->tojson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                         ->warning()->important();
                  }
              } else {
                  flash('Provincia: ('.$oProvincia->codigo.') '.$oProvincia->nombre)->success()->important();
              }
          } catch (Illuminate\Database\QueryException $e){
              flash('Error grave. NO SE PUDO PROCESAR PXRAD '.$e)->error()->important();
              $data['file']['pxrad']='none';
              return view('segmenter/index', ['data' => $data,'epsgs'=> $this->epsgs]);
          }
          $depto_data=MyDB::getDatadepto($tabla,'public');
          foreach ($depto_data as $depto){
              $depto->provincia_id=$oProvincia->id;
              $oProvincia->Departamentos()->save(
                  $oDepto = Departamento::firstOrCreate(
                      ['codigo'=>$depto->codigo
                      ],collect($depto)->toArray()));
                    // Recorro Fracciones leídas del departamento
              $frac_data=MyDB::getDataFrac($tabla,'public',$oDepto->codigo);
              foreach($frac_data as $fraccion){
                  $oDepto->Fracciones()->save($oFraccion = Fraccion::firstOrCreate(['codigo'=>$fraccion->codigo
                        ],collect($fraccion)->toArray()),false);
              }
              //Leo Localidades y recorro
              $loc_data=MyDB::getDataLoc($tabla,'public',$oDepto->codigo);
              foreach($loc_data as $localidad){
                  $localidad->depto_id=$oDepto->id;
                  $oDepto->load('localidades');
                  $oDepto->Localidades()->sync($oLocalidad = Localidad::firstOrCreate(['codigo'=>$localidad->codigo
                      ],collect($localidad)->toArray()),false);
                  $estado = $oLocalidad->wasRecentlyCreated ? ' (nueva) ' : ' (guardada) ';
                  // Busco Aglomerado de la localidad y asigno localidad al aglomerado
                  $aglo_data=MyDB::getDataAglo($tabla,'public',$oLocalidad->codigo);
                  $oLocalidad->Aglomerado()->associate(Aglomerado::firstorCreate(
                       ['codigo'=>$aglo_data->codigo],
                       collect($aglo_data)->toArray()));
                  $oLocalidad->save();

                  // Obtengo, recorro y cargo los radios
                  try {
                      $radio_data = MyDB::getDataRadio ($tabla, 'public', $oLocalidad->codigo);
                  } catch ( GeoestadisticaException $e ) {
                      flash('('.$e->GetCode().') Error. NO SE PUDO PROCESAR PXRAD: '.$e->getMessage())->error()->important();
                      $data['file']['pxrad']='none';
                      Log::debug('Error cargando data Radio '.$e);
                      return view('segmenter/index', ['data' => $data,'epsgs'=> $this->epsgs]);
                  }

                  foreach($radio_data as $radio){
                      $radio->localidad_id=$oLocalidad->id;
                      $oLocalidad->load('radios');
                      $oLocalidad->Radios()->sync($oRadio = Radio::firstOrCreate(
                          ['codigo'=>$radio->codigo],collect($radio)->toArray()),false);
                      $estado=$oRadio->wasRecentlyCreated?' (nueva) ':' (guardada) ';
                      $oRadio->Fraccion()->associate(Fraccion::where('codigo',substr($radio->codigo,0,7))->firstorFail());
                      $oRadio->Tipo()->associate(TipoRadio::firstOrCreate(['nombre'=>$radio->tipo]));
                      $oRadio->save();
                  }
              }
          }
      }
      $data['file']['pxrad']='Si';
    } else  {
      $data['file_pxrad']['pxrad']='none pxrad';
      $data['file']['pxrad']='none';
    }

    if ($request->hasFile('tabla_segmentos')) {
     if($tabla_segmentos_file = Archivo::cargar($request->tabla_segmentos, Auth::user())) {
         flash("Tabla de Segmentos Completa ")->info();
         Log::debug('Tabla de Segmentos: '.$tabla_segmentos_file->tojson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
     } else {
         flash("Error en el modelo cargar archivo, para tabla de segmentos completa")->error();
     }
     $tabla_segmentos_file->procesar();
     if (!$tabla_segmentos_file->procesado) {
         flash($data['file']['error']='Archivo '.$tabla_segmentos_file->nombre_original.' sin Procesar Tabla de Segmentos por error')->important();
         Log::error($data['file']['error'],$tabla_segmentos_file);
     }else{
         $esquema=$tabla_segmentos_file->moverData();
         Log::info('Tabla de Segmentos: '.$tabla_segmentos_file->tojson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
     }
    }

      if(isset($oDepto)){
        //return redirect('/depto/'.$oDepto->id);
        return view('deptoview',['departamento' =>
                           $oDepto->loadCount('localidades')]);
      }else{
          return view('segmenter/index', ['data' => $data,'epsgs'=> $this->epsgs]);
      }
    }
  }
}
