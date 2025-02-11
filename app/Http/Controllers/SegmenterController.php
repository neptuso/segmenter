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
        $mensaje = 'No tiene permiso para cargar o no esta logueado';
        flash($mensaje)->error()->important();
        return $mensaje;
    }else{
      $AppUser = Auth::user();
      $data = [];
      $segmenta_auto = false;
      $pba = false;
      $epsg_id = $request->input('epsg_id')?$request->input('epsg_id'):'epsg:22183';
      $data['epsg']['id'] = $epsg_id;
      flash('SRS: '.$data['epsg']['id']);

    // Se procesa archivo de listado de viviendas C1
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

    $temp = array();
    $algo =  array('link' => 'temporal');
    $temp[0] = (object) $algo;
    $codaglo = isset($codaglo) ? $codaglo : $temp;
    
    // En caso de que vengan capa de etiquetas/poligonos
    if ($request->hasFile('shp_lab')) {
     if($shp_lab_file = Archivo::cargar($request->shp_lab, Auth::user(),
     'shape', [$request->shx_lab, $request->dbf_lab, $request->prj_lab])) {
       flash("Archivo Shp ")->info();
     } else {
       flash("Error en el modelo cargar archivo al procesar SHP")->error();
     }
    $shp_lab_file->epsg_def = $epsg_id;
    $shp_lab_file->tipo = 'shp/lab';
    $shp_lab_file->save();
     if( $ppddllls=$shp_lab_file->procesar(['epsg'=>$epsg_def]) ) {
        flash('Proceso');
     }else{
        flash('la pifio, ver '.$codaglo[0]->link)->warning();
     }
    
    }

    if ($request->hasFile('shp')) {
     if($shp_file = Archivo::cargar($request->shp, Auth::user(),
     'shape', [$request->shx, $request->dbf, $request->prj])) {
       flash("Archivo Shp ")->info();
     } else {
         flash("Error en el modelo cargar archivo al procesar SHP/E00")->error();
     }
     $shp_file->epsg_def = $epsg_id;
     if( $ppddllls=$shp_file->procesar(['epsg'=>$epsg_def]) ) {
        flash('Proceso');
     }else{
        flash('la pifio, ver '.$codaglo[0]->link)->warning();
     }
    MyDB::agregarsegisegd($codaglo[0]->link);

    // PROCESAMIENTO PARA ARCHIVOS e00 o Shapes
    $shp_file->epsg_def = $epsg_id;
    $shp_file->save();
    if( $mensajes=$shp_file->procesar() ) {
      flash('Procesó '.$shp_file->tipo)->important()->success();
      $ppdddllls=$shp_file->pasarData();
    }else{flash('No se pudo procesar la cartografía')->error()->important();
      $mensajes='ERROR';
      $ppdddllls=[];
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
    if (isset($codaglo[0]->link)){
            if ($epsg_id=='sr-org:8333'){
               MyDB::setSRID('e'.$codaglo[0]->link,98333);
            }
    }
  }    
    if($segmenta_auto==true) {
          MyDB::segmentar_equilibrado($codaglo[0]->link,36);
          flash('Segmentado automáticamente a 36 viviendas x segmento')->important();
    }
    if ($request->hasFile('pxrad')) {
     if($pxrad_file = Archivo::cargar($request->pxrad, Auth::user())) {
         flash("Archivo PxRad ")->info();
        } else {
         flash("Error en el modelo cargar archivo")->error();
     }
        $random_name='t_'.$request->pxrad->hashName();
        $data['file_pxrad']['pxrad'] = $request->pxrad->storeAs('segmentador', $random_name); //.'.'.$request->c1->getClientOriginalExtension());
        $original_extension = strtolower($request->pxrad->getClientOriginalExtension());
        $original_name = $request->pxrad->getClientOriginalName();

        //Si no se cargo geometria tomo del nombre del listado los utilmos 8
        //caracteres del nombre, puede ser fecha
        $codaglo=isset($codaglo)?$codaglo:substr($original_name,-13,9);

       if ($original_extension == 'csv'){
    $data['file_pxrad']['csv_info'] = 'Se Cargo un csv en pxrad, no esperado.';
       }
       elseif ($original_extension == 'dbf'){
    $data['file_pxrad']['dbf_info'] = 'Se Cargo una pxrad en formato dbf.';
    // Subo DBF con pgdbf a una tabla temporal.
              $process = Process::fromShellCommandline('pgdbf -s UTF8 $pxrad_dbf_file | psql -h $host -p $port -U $user $db');
    $process->run(null, ['pxrad_dbf_file' => storage_path().'/app/'.$data['file_pxrad']['pxrad'],'db'=>Config::get('database.connections.pgsql.database'),'host'=>Config::get('database.connections.pgsql.host'),'user'=>Config::get('database.connections.pgsql.username'),'port'=>Config::get('database.connections.pgsql.port'),'PGPASSWORD'=>Config::get('database.connections.pgsql.password')]);
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            flash($data['file']['error']=$process->getErrorOutput())->important();
  }else{
            $tabla = strtolower(
      substr($data['file_pxrad']['pxrad'],strrpos($data['file_pxrad']['pxrad'],'/')+1,-4) );
      if (! Schema::hasTable($tabla)){
          $process = Process::fromShellCommandline('pgdbf -s latin1 $pxrad_dbf_file | psql -h $host -p $port -U $user $db');
          $process->run(null, ['pxrad_dbf_file' => storage_path().'/app/'.$data['file_pxrad']['pxrad'],'db'=>Config::get('database.connections.pgsql.database'),'host'=>Config::get('database.connections.pgsql.host'),'user'=>Config::get('database.connections.pgsql.username'),'port'=>Config::get('database.connections.pgsql.port'),'PGPASSWORD'=>Config::get('database.connections.pgsql.password')]);
          flash('La PxRad dbf fue procesada como latin1')->warning()->important();
            }
      // Leo dentro de la tabla importada desde el dbf
      flash($data['file_pxrad']['info']= 'Resultado carga dbf: '.$process->getOutput())->success()->important();
 
      try{
            $procesar_result=MyDB::procesarPxRad($tabla,'public');
            // Busco provincia encontrada en pxrad:
            $prov=MyDB::getCodProv($tabla,'public');
            if($prov==0){
              flash('Error grave. Buscando provincia. NO SE PUDO PROCESAR PXRAD ! ')->error()->important();
              $data['file']['pxrad']='No se pudo procesar PxRad! ';
              return view('segmenter/index', ['data' => $data,'epsgs'=> $this->epsgs]);
            }
        $oProvincia= Provincia::where('codigo', $prov)->first();
        if ($oProvincia==null){
          $prov_data=MyDB::getDataProv($tabla,'public');
          $oProvincia= new Provincia ($prov_data);
          if ($oProvincia->save()){
             flash('Se creó la provincia: '.$oProvincia->tojson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))->warning()->important();
           }
        }else{
           flash('Provincia: ('.$oProvincia->codigo.') '.$oProvincia->nombre)->success()->important();
        }
      }catch (Illuminate\Database\QueryException $e){
        flash('Error grave. NO SE PUDO PROCESAR PXRAD '.$e)->error()->important();
        $data['file']['pxrad']='none';
        return view('segmenter/index', ['data' => $data,'epsgs'=> $this->epsgs]);
      }

      $depto_data=MyDB::getDatadepto($tabla,'public');
//      Log::debug('Deptos data: '.collect($depto_data) ); //.' cantidad: '.count($depto_data));
      foreach($depto_data as $depto){
        $depto->provincia_id=$oProvincia->id;
        //:dd($depto);
        $oProvincia->Departamentos()->save($oDepto = Departamento::firstOrCreate(['codigo'=>$depto->codigo
        ],collect($depto)->toArray()));
//        Log::debug('Depto: '.$oDepto->tojson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                    // Recorro Fracciones leídas del departamento
        $frac_data=MyDB::getDataFrac($tabla,'public',$oDepto->codigo);
        foreach($frac_data as $fraccion){
            $oDepto->Fracciones()->save($oFraccion = Fraccion::firstOrCreate(['codigo'=>$fraccion->codigo
                        ],collect($fraccion)->toArray()),false);
        }

        //Leo Localidades y recorro
        $loc_data=MyDB::getDataLoc($tabla,'public',$oDepto->codigo);
        foreach($loc_data as $localidad){
//            dd($oDepto,$localidad,Localidad::firstOrNew(collect($localidad)->toArray()));
            $localidad->depto_id=$oDepto->id;
            $oDepto->load('localidades');
            $oDepto->Localidades()->sync($oLocalidad = Localidad::firstOrCreate(['codigo'=>$localidad->codigo
      ],collect($localidad)->toArray()),false);
      $estado=$oLocalidad->wasRecentlyCreated?' (nueva) ':' (guardada) ';
//                  Log::debug('Localidad: '.$estado.$oLocalidad->tojson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
//      $aLocalidades[]=Localidad::firstOrNew(collect($localidad)->toArray());

      // Busco Aglomerado de la localidad y asigno localidad al aglomerado
      $aglo_data=MyDB::getDataAglo($tabla,'public',$oLocalidad->codigo);
      $oLocalidad->Aglomerado()->associate(Aglomerado::firstorCreate(['codigo'=>$aglo_data->codigo],
        collect($aglo_data)->toArray()));
      $oLocalidad->save();

                        // Obtengo, recorro y cargo los radios
                  $radio_data=MyDB::getDataRadio($tabla,'public',$oLocalidad->codigo);
      foreach($radio_data as $radio){
                $radio->localidad_id=$oLocalidad->id;
                            $oLocalidad->load('radios');
                            $oLocalidad->Radios()->sync($oRadio = Radio::firstOrCreate(['codigo'=>$radio->codigo
          ],collect($radio)->toArray()),false);

          $estado=$oRadio->wasRecentlyCreated?' (nueva) ':' (guardada) ';
          $oRadio->Fraccion()->associate(Fraccion::where('codigo',substr($radio->codigo,0,7))->firstorFail());
          $oRadio->Tipo()->associate(TipoRadio::firstOrCreate(['nombre'=>$radio->tipo]));
                            $oRadio->save();
  //                          Log::debug('Radio: '.$estado.$oRadio->tojson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        }
        }
      }

//      Log::debug('Provincia: '.$oProvincia->tojson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  }
       }
      $data['file']['pxrad']='Si';
    }else{
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
