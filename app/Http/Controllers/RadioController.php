<?php

namespace App\Http\Controllers;

use App\Model\Radio;
use Illuminate\Http\Request;
use App\Segmentador;
use Illuminate\Support\Facades\Log;
use App\Model\TipoRadio;

class RadioController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\Radio  $radio
     * @return \Illuminate\Http\Response
     */
    public function show(Radio $radio)
    {
        //
        return $radio->load(['fraccion','localidades']);
          flash(
                ($radio
                    ->load(['fraccion','localidades'])
                )->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
          )->important();
          Log::debug($radio->esquema);
          Log::debug(collect($radio->esquemas)->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
          return view('home');

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\Radio  $radio
     * @return \Illuminate\Http\Response
     */
    public function edit(Radio $radio)
    {
       //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Radio  $radio
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Radio $radio)
    {
       //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\Radio  $radio
     * @return \Illuminate\Http\Response
     */
    public function destroy(Radio $radio)
    {   
        $id_localidad = $radio->localidades[0]->id;
        $radio ->delete();
        return redirect()->route('Ver-Localidad', $id_localidad)->with('info','Se eliminó el radio '. $radio->codigo);
     
    }

    /**
     * Segmentar radio a lados completos
     * 
     */
    public function segmentar(Radio $radio,$deseadas,$max,$min,$indivisible)
    {
        //
        $aglo=$radio->aglomerado->codigo();
        $segmenta = new Segmentador();
        $segmenta->segmentar_a_lado_completo($radio,$deseadas,$max,$min,$indivisible);
        return $segmenta->ver_segmentacion($radio);
    }

    /**
     * Juntar segmentos con menos de n viviendas 
     * 
     */
    public function juntarSegmentos(Radio $radio,$menos_n_viviendas)
    {
        //
        $aglo=$radio->aglomerado->codigo();
        $segmenta = new Segmentador();
        $segmenta->juntarSegmentos($radio,$menos_n_viviendas);
        return $segmenta->ver_segmentacion($radio);
    }

    /**
     * Cambio de Tipo de radio  
     * 
     */
    public function cambiotiporadio(Request $request, $radio_id){

        
        $radio = Radio::findorfail($radio_id);
        $tipoderadio = TipoRadio::where ('id', '=', $radio->tipo_de_radio_id)->first('nombre');
        if ($radio->localidades->count() > 1 and $tipoderadio->nombre == 'M'){
            flash('No se puede cambiar el tipo de radio  dado que contiene más de una localidad');
            return back();
        }
        $radio->tipo()->associate(TipoRadio::where('nombre', '=', $request->input('tipo_nuevo'))->first('id'));
        $radio->save();
        flash ('Cambio de Tipo de Radio realizado a ' . $radio->codigo . ' ahora es ' .$request->input('tipo_nuevo') )->success();
        return back();
    }
    
}
