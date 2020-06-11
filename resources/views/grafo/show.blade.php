@extends('layouts.app')
@section('content')
<div>Grafo de segmentación ({{ $aglomerado->codigo}}) {{ $aglomerado->nombre}}</div>
<div>Radio: {{ $radio->codigo}}</div>
<pre style="line-height: initial;font-size: 75%;">
{{ $radio->Resultado ?? 'No hay resultado de segmenta' }}
</pre>
<div id ="resumen"></div>
<canvas id="canvas" style="padding: 20px 50px 20px 50px; max-height: 600px; " height="280" width="600"></canvas>
@endsection
@section('header_scripts')
<script src="/js/numeric-1.2.6.js"></script>
<script src="/js/cytoscape.min.js"></script>
<script src="/js/layout-base.js"></script>
<script src="/js/cose-base.js"></script>
<script src="/js/cytoscape-fcose.js"></script>
<script src="/js/cytoscape-cola.js"></script>
<script src="/js/cola.min.js"></script>
<style>
#cy {
  width: 1000px;
  height: 600px;
  display: block;
}
</style>
@endsection
@section('content_main')
	<button onClick="ordenar();"value="Ordenar">ReOrdenar</button>
	<div width= 1000px;
         height= 600px;
         id=cy>
    </div>
@endsection
@section('footer_scripts')
	<script>
    let arrayOfClusterArrays = @json($segmentacion) ;  
    let clusterColors = ['#FF0', '#0FF', '#F0F', '#4139dd', '#d57dba', '#8dcaa4'
                        ,'#555','#CCC','#A00','#0A0','#00A','#F00','#0F0','#00F','#008','#800','#080'];
		var cy = cytoscape({

  container: document.getElementById('cy'), // container to render in

  elements: [ // list of graph elements to start with
    @foreach ($nodos as $nodo)
        { data: { group: 'nodes',mza: '{{ $nodo->mza_i }}',label: '{{ $nodo->label }}', conteo: '{{ $nodo->conteo }}', id: '{{ $nodo->mza_i }}-{{ $nodo->lado_i }}'  } },
    @endforeach    
    @foreach ($relaciones as $nodo)
        { data: { group: 'edges',tipo: '{{ $nodo->tipo }}', id: '{{ $nodo->mza_i }}-{{ $nodo->lado_i }}->{{ $nodo->mza_j }}-{{ $nodo->lado_j }}', source:'{{ $nodo->mza_i }}-{{ $nodo->lado_i }}', target:'{{ $nodo->mza_j }}-{{ $nodo->lado_j }}'} },
    @endforeach    
  ],
  style: [ // the stylesheet for the graph
    {
      selector: 'node',
      style: {
        'background-color': function (ele) {
					for (let i = 0; i < arrayOfClusterArrays.length; i++)
						if (arrayOfClusterArrays[i].includes(ele.data('id')))
                           if (i>clusterColors.length) {n=i-clusterColors.length;
                                                        if (n<0) n=-n;}
                            else n=i;
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
        'label': function (ele) { return ''; if (ele.data('tipo')=='dobla') return 'd'; else if (ele.data('tipo')=='enfrente') return 'e'; }
      }
    }
      ],
    layout: {
        name: 'grid',
        rows: 30
    }
    });
    var layout = cy.layout({ name: 'random'});
    layout.run();
    function ordenar(){
        var layout = cy.layout({ name: 'cose'});
        layout.run();
    }
    ordenar();
    </script>
       <script src="/js/Chart.bundle.js" charset="utf-8"></script>
        <script>
        var url = "{{url('ver-segmentacion-lados-grafico-resumen')}}/{{$aglomerado->id}}";
        var SegmentosCantidad = new Array();
        var Labels = new Array();
        var Viviendas = new Array();
        $(document).ready(function(){
          $.post(url, {"_token": "{{ csrf_token() }}"},function(response){
            var sum = 0;
            var n_segs= 0; 
            response.forEach(function(data){
                SegmentosCantidad.push(data.cant_segmentos);
                Viviendas.push(data.vivs);
                sum += Number(data.vivs)*Number(data.cant_segmentos);
                n_segs += Number(data.cant_segmentos);
            });
            var mensaje = n_segs+' segmentos para '+sum+' viviendas, con un promedio de '+sum/n_segs+' viviendas x segmento';
            document.getElementById("resumen").innerHTML=mensaje;
            
            var ctx = document.getElementById("canvas").getContext('2d');
                var myChart = new Chart(ctx, {
                  type: 'bar',
                  data: {
                      labels: Viviendas,
                      datasets: [{
                          label: 'Número de Segmentos',
                          data: SegmentosCantidad,
                          borderWidth: 1,
                          backgroundColor: 'rgb(36, 125, 173)',
                          borderColor: 'rgb(66, 155, 213)'
                      }]
                  },
                  options: {
                      tooltips: {
                        callbacks: {
                            title: function(tooltipItem, data) { return 'Cantidad de Viviendas: '+tooltipItem[0].xLabel }
                        }
                       },
                      responsive: true,
                      scales: {
                          yAxes: [
                              {
                              gridLines: {
                                  drawBorder: true,
                                  color: ['pink', 'red', 'orange', 'yellow', 'green', 'blue', 'indigo', 'purple']
                              },
                              ticks: {
                                  beginAtZero:true
                              }
                          }]
                      }
                  }
              });
          });
        });
        </script>
@endsection
