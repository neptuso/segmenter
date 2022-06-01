@extends ('layouts.app')

@section('title', 'Edición')

@section ('content')
<div class="container">
 <div class="row">
  <div class="col-md-6">
    @auth ('admin')
      // The user is authenticated...
      Autenticado! 
    @endauth
    @if (isset($user))
        Usuario: {{ $user->name }} </br>
    @else
      Usuario no identificado ! Identifiquese o retirese por favor.<br />
    @endif
    @if (isset($localidad))
     Localidad (<a href="{{ url("/localidad/{$localidad->id}") }}" >{{ $localidad->codigo }} 
     <b> {{ $localidad->nombre }} </b></a>)<br />
    @else
      NO está definida ningúna localidad.<br />
    @endif
    @if (isset($aglomerado))
      del aglomerado <a href="{{ url("/aglo/{$aglomerado->id}") }}" >
      ({{ $aglomerado->codigo }}) 
      <b> {{ $aglomerado->nombre }} </b></a><br />
    @else
      NO está definido ningún aglomerado.<br />
    @endif
    @if ((isset($localidad)) and $oDepto = $localidad->departamentos()->first())
      en el Departamento <a href="{{ url("/depto/{$oDepto->id}") }}" >
      ({{ $oDepto->codigo }}) 
      <b> {{ $oDepto->nombre }} </b></a><br />
      <a href="{{ url("/prov/{$oDepto->provincia->id}") }}" >
      ({{ $oDepto->provincia->codigo }}) 
      <b> {{ $oDepto->provincia->nombre }} </b></a>
    @else
      NO está definido ningún departamento.<br />
    @endif
  </div>
  <div class="col-md-6 text-center">
    @auth
       <button type="button" class="btn btn-danger" id="eliminar">Eliminar</button>
    @endauth
  </div>
 </div>
</div>

 <div class="container">
   <!-- Modal -->
   <div class="modal fade" id="edicionModal" role="dialog">
    <div class="modal-dialog">

     <!-- Modal content-->
     <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Eliminar</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="modal-body-edicion">

      </div>
      <div class="modal-footer">
       <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
      </div>
     </div>
    </div>
   </div>

 <hr />
 <div class="form-horizontal text-center">
  <form action="/edicion/" method="DELETE" enctype="multipart/form-data">
                @csrf
   <div class="form-group text-left">
  
   </div>
  </form>
 </div>
</div>
</div>
@stop
@section('footer_scripts')
<script>
 $(document).ready( function () {
     $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
     });
     $("#eliminar").click(function () {
      // AJAX request
           $.ajax({
            url: "{{ url('edicion') }}"+"/eliminar",
            type: 'post',
            data: {id: 1,format: 'html'},
            success: function(response){ 
              // Add response in Modal body
              $('#modal-body-edicion').html(response);
              // Display Modal
              $('#edicionModal').modal('show'); 
            }
           });
     });
 });
</script>
@endsection
