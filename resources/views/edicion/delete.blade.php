<div class="container">
<h3>Eliminar PxRad cargada</h3>
<p>Cuidado está próximo a eliminar información importante de la Base de Datos</p>
    @auth
       <button type="button" class="btn btn-danger" id="eliminar">Eliminar</button>
    @endauth
</div>
@section('script')

    var prov = $('#prov').val();
    $.get( "/getDeptos", { prov: prov } ).done(function( data ) {
        $.each(data, function(i, value){
            $('#deptos_names').append($('<option>').text(value).attr('value', value));
        });
    });
@stop
