formilario de edicion
@extends('layouts.app')
@section('content')
<div class="container">


<br>


<form action="{{ url('/radio/'.$radio->id ) }}" method="post">
@csrf
{{method_field('PATCH')}}
@include('radio.form', ['modo'=>'editar']);


</form>
</div>
@endsection