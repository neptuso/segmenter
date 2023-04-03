@extends('layouts.app')
@section('content')
@if ($verbotones)
  <a href = "{{ url("edit/si")}}" id="editmode"> Apagar modo edición </a>
@else
  <a href = "{{ url("edit/si")}}" id="editmode"> Encender modo edición</a>
@endif
<div class = "row center"><div class = "col-lg-12 text-center">
<h4><a href = "{{ url("/aglo/{$aglomerado->id}") }}" > ({{ $aglomerado->codigo}}) {{ $aglomerado->nombre}}</a></h4>

<h4>Radio: {{ substr($radio->codigo, 0, 2) }} {{ substr($radio->codigo, 2, 3) }} 
    <b>
        {{ substr($radio->codigo, 5, 2) }} {{ substr($radio->codigo, 7, 2) }}
    </b>
    @if ($verbotones)
        @can('Eliminar Radio')
            @if($radio->localidades->count() == 1)
              <form action="/radio/{{$radio->id}}" id="EliminarRadio" method = "POST">
                @csrf
                @method('DELETE')
                <!-- El boton de abajo debería ser type button para que abra la consulta del onclick pero por alguna razon no funciona -->
                <button type="submit" onclick="EliminarRadio({{$radio->codigo}})" class="btn btn-danger">
                    Eliminar Radio 
                </button>
              </form>
            @endif
        @endcan
      @endif
</h4>

@if($radio->tipo)	
  <p class = "text-center">({{ $radio->tipo->nombre }}) {{ $radio->tipo->descripcion }} 
    @if ($verbotones)
      @can('Modificar Tipo Radio')
        <form action="/radio/{{$radio->id}}" id="formeditradio" method="POST"> 
            @csrf
            @if($radio->tipo->nombre == "M")
                <input type="hidden" value='U' name='tipo_nuevo'>
                <button type="button" onclick="CambiarTipodeRadio()" class="btn btn-danger" id="cambiotipor" > Cambiar a Urbano </button>
            @elseif($radio->tipo->nombre == "U") 
                <input type="hidden" value='R' name='tipo_nuevo'>
                <button type="button" onclick="CambiarTipodeRadio()" class="btn btn-danger" id="cambiotipor" > Rural </button>                         
            @elseif($radio->tipo->nombre == "R")
                <input type="hidden" value='M' name='tipo_nuevo'>
                <button type="button" onclick="CambiarTipodeRadio()" class="btn btn-danger" id="cambiotipor" > Cambiar a Mixto </button>
            @endif
        </form>
      @endcan
    @endif
  </p>  
@endif

<h5>
<div class = "d-flex justify-content-around" >  

  @foreach ($radio->localidades->sortBy('codigo') as $loc)
    <div> 
      <a 
          @if ( $loc->id == $localidad->id ) 
            style = "
            color: #dd8a32;
            text-decoration: crimson;
            font-weight: bolder;
            font-size: 1.2rem;
            "
          @endif
          href = "{{ url("/localidad/{$loc->id}") }}" > 
          ({{$loc->codigo}}) {{ $loc->nombre}}
      </a>
        @can('Desvincular Radio Localidad')
          @if ($loc->id !== $localidad->id)
            @if($verbotones)
              <form action="/localidad/{{$loc->id}}" id="eliminarelacionlocalidad" method="POST">   
                @csrf
                <input type="hidden" value="{{$radio->id}}" name='radio_id'>
                <!-- El boton de abajo debería ser type button para que abra la consulta del onclick pero por alguna razon no funciona -->
                <button type="submit" onclick="EliminarRelacionLocalidad({{$radio->codigo}}, {{$loc->codigo}})" class="btn btn-danger" id="eliminarelacion">
                    Eliminar Relacion con Localidad
                </button>
              </form>
            @endif
          @endif
        @endcan
    </div>      
  @endforeach
</div>
</h5>

  
@if($radio->viviendas)	<p class="text-center">Con {{ $radio->viviendas }} viviendas.</p> @endif

</div>
</div>
  <div class="row">
    </div>
</div>
@endsection
@section('content')
@endsection
@section('header_scripts')

<style>
#grafo_cy {
  width: 480px;
  height: 500px;
  display: block;
}
.no-gutters {
  margin-right: 0;
  margin-left: 0;

  > .col,
  > [class*="col-"] {
    padding-right: 0;
    padding-left: 0;
  }
}
</style>
@endsection
@section('content_main')
<div class = "container-xl" >
    <div class = "no-gutters row ">
        @forelse ($segmentacion_data_listado as $segmento)
            @if($loop->first)
                <div class = "no-gutters row ">
                    Se encontraron {{ $loop->count }} segmentos.
	              </div>
                <div class = "table">
                <div class = "row ">
                <div class = "col-sm-1 text-center border"> Seg </div>
                <div class = "col-sm-10 text-center border"> Descripción </div>
                <div class = "col-sm-1 text-center border"> Viviendas </div>
	    </div>
        @endif
        <div class = "row border">
        <div class = "col-sm-1 ">{{ $segmento->seg }}</div>
        <div class = "col-sm-10 ">{!! str_replace(". Manzana ",".<br/>Manzana ",
                                            str_replace(".  ",".<br/>",$segmento->detalle))  !!}</div>
        <div class = "col-sm-1 text-right "><p class = "text-right">{{ $segmento->vivs }}</p></div>
	  </div>
       @if($loop->last)
       </div>
       @endif

       @empty
            <p>No hay segmentos</p>
       @endforelse
      </div>
      <div class="row no-gutters">
        <div class="col-md-6 " title=MiniMap> {!! $radio->getSVG() !!}</div>
        <div class="col-md-6 ">
            <div id=grafo_cy width= 400px; height= 500px
               title="Grafo de adyacencias" >
            </div>
            <div class="text-center">
       	     <button type="button" class="btn btn-primary" onClick="ordenar();"value="Ordenar">ReOrdenar</button>
            </div>
        </div>
      </div>
      <div class="row no-gutters">
        <div class="col-sm-12">
        <pre style="line-height: initial;font-size: 75%;">
        {{ $radio->resultado ?? 'No hay resultado de segmenta' }}
        </pre>
        </div>
      </div>
    </div>
</div>
@endsection
@section('footer_scripts')
	<script>
    var transformMatrix = [1, 0, 0, 1, 0, 0];
    var svg = document.getElementById('radio_{{$radio->codigo}}');
    var viewbox = svg.getAttributeNS(null, "viewBox").split(" ");
    var centerX = parseFloat(viewbox[2]) / 2;
    var centerY = parseFloat(viewbox[3]) / 2;
    var matrixGroup = svg.getElementById("matrix-group");


    function pan(dx, dy) {     	
      transformMatrix[4] += dx;
      transformMatrix[5] += dy;
                
      var newMatrix = "matrix(" +  transformMatrix.join(' ') + ")";
      matrixGroup.setAttributeNS(null, "transform", newMatrix);
    }

    function EliminarRelacionLocalidad($radio,$loc){
      var message = "Está seguro que desea desvincular el radio cod {{$radio->codigo}} (id: {{$radio->id}}) de la localidad cod {{$loc->codigo}} (id: {{$loc->id}})?";
      if (confirm(message)){
        $("#eliminarelacionlocalidad").submit();
        //return false;
      }
    }
    
    function EliminarRadio($radio){
        r = "Está seguro que desea eliminar el radio cod {{$radio->codigo}} (id: {{$radio->id}}) ?";
        if (confirm(r)){
          $("#EliminarRadio").submit();
        }
    }     
  
    function CambiarTipodeRadio(){
      mensaje = "Desea cambiar el tipo de radio?";
      if(confirm(mensaje)){
          $("#formeditradio").submit();
      } 
    }
    function CambiarTipodeRadioaRural(){
      mensaje = "Cambiar a Rural, no te pregunto";
      if (confirm(mensaje)){
        $("#")
      }

    }
      function zoom(scale) {
      for (var i = 0; i < 4; i++) {
        transformMatrix[i] *= scale;
      }
      transformMatrix[4] += (1 - scale) * centerX;
      transformMatrix[5] += (1 - scale) * centerY;  

      svg.viewBox.baseVal.x*=scale;
      svg.viewBox.baseVal.y*=scale;
      //  transformMatrix[4] = centerX;
      //  transformMatrix[5] = centerY;
              
      var newMatrix = "matrix(" +  transformMatrix.join(' ') + ")";
      matrixGroup.setAttributeNS(null, "transform", newMatrix);
      console.log(svg.getAttributeNS(null, "viewBox").split(" "));
      console.log(scale);
      console.log(transformMatrix);
    }


    let arrayOfClusterArrays = @json($segmentacion) ;  
    let clusterColors = ['#FF0', '#0FF', '#F0F', '#4139dd', '#d57dba', '#8dcaa4'
                        ,'#555','#CCC','#A00','#0A0','#00A','#F00','#0F0','#00F','#008','#800','#080'];
		var cy = cytoscape({

    container: document.getElementById('grafo_cy'), // container to render in

  elements: [ // list of graph elements to start with
    @if($nodos)
    @foreach ($nodos as $nodo)
        { data: { group: 'nodes',mza: '{{ $nodo->mza_i }}',label: '{{ $nodo->label }}', conteo: '{{ $nodo->conteo }}', id: '{{ $nodo->mza_i }}-{{ $nodo->lado_i }}'  } },
    @endforeach    
    @foreach ($relaciones as $nodo)
        { data: { group: 'edges',tipo: '{{ $nodo->tipo }}', id: '{{ $nodo->mza_i }}-{{ $nodo->lado_i }}->{{ $nodo->mza_j }}-{{ $nodo->lado_j }}', source:'{{ $nodo->mza_i }}-{{ $nodo->lado_i }}', target:'{{ $nodo->mza_j }}-{{ $nodo->lado_j }}'} },
    @endforeach    
    @else
        { data: { group: 'nodes',mza: 'A', label: 'A', conteo: '1', id: 'A-1'  } },
        { data: { group: 'edges',tipo: 'test', id: 'A-1->A-1', source:'A-1', target:'A-1'} },
    @endif
  ],
  style: [ // the stylesheet for the graph
    {
      selector: 'node',
      style: {
        'background-color': function (ele) {
          let n=1;
					for (let i = 0; i < arrayOfClusterArrays.length; i++)
						if (arrayOfClusterArrays[i].includes(ele.data('id'))){
                           //Seteo id de segmentacino en node
                           ele.seg=i; 
                           if (i>clusterColors.length) {n=i-clusterColors.length;
                                                        if (n<0) n=-n;}
                            else n=i;
            }
						if (clusterColors[n]!=null) return clusterColors[n];
					return '#000000';
				},
        'label': 'data(conteo)',
        'width': function(ele){ return (ele.data('conteo')/2)+10; },
        'height': function(ele){ return (ele.data('conteo')/2)+10; },
      }
    },
    {
      selector: 'edge',
      style: {
        'width': 3,
        'line-color': function (ele) { if (ele.data('tipo')=='dobla') return '#555'; else return '#ccc'; },
        'target-arrow-color': '#aae',
        'target-arrow-shape': 'triangle',
        'label': function (ele) { return ''; if (ele.data('tipo')=='dobla')
        return 'd'; else if (ele.data('tipo')=='enfrente') return 'e'; else
        return 'o'; }
      }
    }
      ],
    layout: {
        name: 'grid',
        rows: 9
    }
    });
    cy.on('click', 'node', function(evt){
      var node = evt.target;
      alert( 'Lado: ' + node.id() );
    });
    cy.on('mouseover', 'node', function(evt){
      var node = evt.target;
      console.log( 'Viviendas: ' + node.style('label') + ' Mza:lado: ' + node.data('label' ) + ' Segmento: '+node.seg);
          let n=1;
					for (let i = 0; i < arrayOfClusterArrays.length; i++)
						if (arrayOfClusterArrays[i].includes(node.data('id'))){
              console.log(i);
          }
    });
//    var layout = cy.layout({ name: 'cose'});
//    layout.run();
    function ordenar(){
        var layout = cy.layout({ name: 'cose'});
        layout.run();
    }
    ordenar();
</script>
@endsection
