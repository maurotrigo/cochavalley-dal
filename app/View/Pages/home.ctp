<style type="text/css">
	#map_canvas { height: 500px; }
</style>
<script type="text/javascript">
	
google.load('visualization', '1');

var map;
var areaTableName = '1IW43AblTgBE6OGz79hqMjq3yv_saG88OuE4pBJk';
var crimeTableName = '1S-QVAbfIrQKuT00Iw49x7ZXrRPNRYzHp1h92JkU';
var apiKey = 'AIzaSyB1EjUV_8Lmq6YkAQ04jwRttfGft94bXX0';

var crimeLayer = null;
var areaLayer = null;

var layerStyles = {
	'poblacion': [
		{
			'min': 0,
			'max': 1000,
			'color': '#FFD699'
		},
		{
			'min': 1000,
			'max': 10000,
			'color': '#FFC166'
		},
		{
			'min': 10000,
			'max': 500000,
			'color': '#FFAD33'
		},
		{
			'min': 500000,
			'max': 1000000,
			'color': '#FF9900'
		}
	],
	'cob_ap': [
		{
			'min': 0,
			'max': 20,
			'color': '#CCEAFF'
		},
		{
			'min': 20,
			'max': 40,
			'color': '#99D6FF'
		},
		{
			'min': 40,
			'max': 60,
			'color': '#66C1FF'
		},
		{
			'min': 60,
			'max': 80,
			'color': '#33ADFF'
		},
		{
			'min': 80,
			'max': 100,
			'color': '#0099FF'
		}
	],
	'cob_san': [
		{
			'min': 0,
			'max': 20,
			'color': '#99EA99'
		},
		{
			'min': 20,
			'max': 40,
			'color': '#99EA99'
		},
		{
			'min': 40,
			'max': 60,
			'color': '#66E066'
		},
		{
			'min': 60,
			'max': 80,
			'color': '#33D633'
		},
		{
			'min': 80,
			'max': 100,
			'color': '#00CC00'
		}
	],
}

function applyStyle(map, layer, column) {
	var columnStyle = layerStyles[column];
	var styles = [];
	
	for (var i in columnStyle) {
		var style = columnStyle[i];
		styles.push({
			where: generateWhere(column, style.min, style.max),
			polygonOptions: {
				fillColor: style.color,
				fillOpacity: style.opacity ? style.opacity : 0.8
			}
		});
	}
	
	layer.set('styles', styles);
}

function generateWhere(columnName, low, high) {
	var whereClause = [];
	whereClause.push("'");
	whereClause.push(columnName);
	whereClause.push("' >= ");
	whereClause.push(low);
	whereClause.push(" AND '");
	whereClause.push(columnName);
	whereClause.push("' < ");
	whereClause.push(high);
	return whereClause.join('');
}



function initialize() {
	var lapaz = new google.maps.LatLng(-16.46463897, -68.14933062);
	var mapOptions = {
		center: lapaz,
		zoom: 12,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};
	map = new google.maps.Map(document.getElementById('map_canvas'), mapOptions);
	
	// Try HTML5 geolocation
	/*if(navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(function(position) {
		var pos = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
		
		
		map.setCenter(pos);
		}, function() {
		handleNoGeolocation(true);
		});
	} else {
		// Browser doesn't support Geolocation
		handleNoGeolocation(false);
	} */
	
	
	var query = "SELECT latitude, longitude, crime_type FROM 1S-QVAbfIrQKuT00Iw49x7ZXrRPNRYzHp1h92JkU";
	 query = encodeURIComponent(query);
	 var gvizQuery = new google.visualization.Query(
		 'http://www.google.com/fusiontables/gvizdata?tq=' + query);

	 var createMarker = function(coordinate, crime_type) {
		
	   if(crime_type=='HECHO DE TRANSITO')
		image = 'http://www.onsc.gob.bo/crimen/icons/crimescene.png';
	   else if (crime_type=='TWITTER'){
		 image = 'http://wwwimages.adobe.com/www.adobe.com/content/dam/Adobe/en/devnet/enterprise-platform/twitter/twitter_small_1307050985_2229.png.adimg.mw.58.png';
	   }
	   else{
		 image = 'http://www.onsc.gob.bo/crimen/icons/theft.png';
	   }
	   var marker = new google.maps.Marker({
		 map: map,
		 position: coordinate,
		 icon: new google.maps.MarkerImage(image)
	   });
	   google.maps.event.addListener(marker, 'click', function(event) {
		 infoWindow.setPosition(coordinate);
		 infoWindow.open(map);
	   });
	 };

	gvizQuery.send(function(response) {
	  var numRows = response.getDataTable().getNumberOfRows();
	  for (var i = 0; i < numRows; i++) {
		var lat = response.getDataTable().getValue(i, 0);
		var lng = response.getDataTable().getValue(i, 1);
		var crime_type = response.getDataTable().getValue(i, 2);;
		var coordinate = new google.maps.LatLng(lat, lng);

		createMarker(coordinate,crime_type);
	  }
	});


	$.getJSON("http://localhost:8083/tweets/?callback=?", 
		function(jsondata){
			$.each(jsondata.results, function(i,item){
				lat = (Math.random() * (16.5 - 16.49) + 16.49)*-1;
				long = (Math.random() * (68.15 - 68.1) + 68.1)*-1;
				console.log(item.id_str);
				if(item.geo!=null){
					if(item.geo.coordinates[0]!=0.000000){
						lat = item.geo.coordinates[0];
						long = item.geo.coordinates[1];
					}
				}
				var coordinate = new google.maps.LatLng(lat, long);
				createMarker(coordinate,'TWITTER');
			});
		}
	);
	
	areaLayer = new google.maps.FusionTablesLayer({
		query: {
			select: 'poblacion',
			from: areaTableName
		}
	});
	
	areaLayer.setMap(map);
	//AM: esto lo jode
	//applyStyle(map, areaLayer, $('#municipios_params select:first').find('option:selected').val());
	
	
 

}



google.maps.event.addDomListener(window, 'load', initialize);

$(document).ready(function () {

	$("#area_layer").click(function() {
		areaLayer.setMap(($(this).is(":checked") ? map : null));
	});
	
	//AM: esto cambiar
	/*$("#crime_layer").click(function() {
		crimeLayer.setMap(($(this).is(":checked") ? map : null));
	});
	*/

	
}); //MT: end $(document).ready()
</script>

<div class="row-fluid">
        <div class="span2">
			
		  <!---
          <div class="well sidebar-nav">
            <ul class="nav nav-list">
              <li class="nav-header">Sidebar</li>
              <li class="active"><a href="#">Link</a></li>
              <li><a href="#">Link</a></li>
              <li><a href="#">Link</a></li>
              <li><a href="#">Link</a></li>
              <li class="nav-header">Sidebar</li>
              <li><a href="#">Link</a></li>
              <li><a href="#">Link</a></li>
              <li><a href="#">Link</a></li>
              <li><a href="#">Link</a></li>
              <li><a href="#">Link</a></li>
              <li><a href="#">Link</a></li>
              <li class="nav-header">Sidebar</li>
              <li><a href="#">Link</a></li>
              <li><a href="#">Link</a></li>
              <li><a href="#">Link</a></li>
            </ul>
          </div>
		  -->
        </div><!--/span-->
        <div class="span8">
			<div id="map_container" style="margin-right: 20px;">
				<div id="map_canvas"></div>
			</div>
        </div><!--/span-->
		<div class="span2">
			<?php echo $this->element('twitter'); ?>
			
		</div>
      </div><!--/row-->
      <hr>
<script type="text/javascript">
	
	var mapContainer = $("#map_container");
    
    function adjustMap() {
        var maxHeight = $(window).height() - mapContainer.offset().top - 16;
		mapContainer.height(maxHeight);
    }
    
	$("#layers").change(function() {
		switch($(this).find("option:selected").val()) {
			case 'all': {
				crimeLayer.setMap(map);
				areaLayer.setMap(map);
				break;
			}
			case 'dots': {
				crimeLayer.setMap(map);
				areaLayer.setMap(null);
				break;
			}
			case 'area': {
				crimeLayer.setMap(null);
				areaLayer.setMap(map);
				break;
			}
		}
	});
	

</script>
