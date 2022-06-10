
borrador formulario ABM RAdios
@extends('layouts.app')
@section('content')
<div class="container">


<form action="{{url('/radio')}}" method="post" >
    @csrf
@include('radio.form',['modo'=>'Editar']);

   

</form>    

</div>
@endsection

