
 <div class="form-group"> 
    <label for="codigo"> Codigo de radio</label>
    <input type="text" class="form-control" name = "codigo" value="{{ isset($radio->id)? $radio->id:'' }}" id = "codigo">
   
 </div>

  <div class="form-group">
    <label for="nombre"> Radio</label>
    <input type="text" class="form-control" name = "nombre" value="{{isset($radio->nombre)? $radio->nombre:'' }}" id = "nombre">
   
</div>    

<div class="form-group">
    <label for="fraccion_id"> Fraccion id </label>
    <input type="text" class="form-control" name = "fraccion_id" value="{{isset($radio->fraccion_id)? $radio->fraccion_id:'' }}" id = "fraccion_id">
   
</div>

<div class="form-group">
    <label for="tipo_de_radio_id"> Tipo de radio id </label>
    <input type="text" class="form-control" name = "tipo_de_radio_id" value="{{isset($radio->tipo_de_radio_id)?$radio->tipo_de_radio_id:''}}" id = "tipo_de_radio_id">
   
</div>

    <label for "Guardar" >  </label>
    <input type="submit" class= "btn btn-success" value = " Guardar  RADIO" name = "Guardar" >
    <br>

      <a class= "btn btn-primary" href="{{url('/radio')}}"> Volver al listado de radios </a>
    
      <br>