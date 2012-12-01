<style type="text/css">
	#map_canvas { height: 500px; }
</style>
<script type="text/javascript">
	
google.load('visualization', '1');

var map;
var areaTableName = '1IW43AblTgBE6OGz79hqMjq3yv_saG88OuE4pBJk';
var crimeTableName = '1S-QVAbfIrQKuT00Iw49x7ZXrRPNRYzHp1h92JkU';
var apiKey = 'AIzaSyB1EjUV_8Lmq6YkAQ04jwRttfGft94bXX0';

var crimeMarkers = [];

var crimeLayer = null;
var heatLayer = null;
var areaLayer = null;

function initialize() {
	var lapaz = new google.maps.LatLng(-16.46463897, -68.14933062);
	var mapOptions = {
		center: lapaz,
		zoom: 6,
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
	
	
	var query = "SELECT latitude, longitude, crime_type FROM " + crimeTableName;
	 query = encodeURIComponent(query);
	 var gvizQuery = new google.visualization.Query(
		 'http://www.google.com/fusiontables/gvizdata?tq=' + query);

	var createCrimeMarker = function(coordinate, crimeType) {
		
		if(crimeType == 'HECHO DE TRANSITO')
			iconImage = 'http://www.onsc.gob.bo/crimen/icons/crimescene.png';
		else{
			iconImage = 'http://www.onsc.gob.bo/crimen/icons/theft.png';
		}
		
		var marker = new google.maps.Marker({
			map: map,
			position: coordinate,
			icon: new google.maps.MarkerImage(iconImage)
		});
		
		
		//google.maps.event.addListener(marker, 'click', function(event) {
		//	infoWindow.setPosition(coordinate);
		//	infoWindow.open(map);
		//});
		
		var crimeTypeId = crimeType;		
		//crimeTypeId.split(' ').join(' ');
		crimeTypeId = crimeTypeId.replace(/ /g, '_').replace(/-/g, '_').toLowerCase();
		
		if (typeof crimeMarkers[crimeTypeId] == "undefined") {
			crimeMarkers[crimeTypeId] = [];
		}
		crimeMarkers[crimeTypeId].push(marker);
		
		if ($("#sub_" + crimeTypeId).length  == 0) {
			$("#crime_sub_layers").append('<li id="sub_' + crimeTypeId + '"><label><input type="checkbox" class="crimeSubType"  name="' + crimeTypeId + '" checked="checked"> ' + crimeType.charAt(0).toUpperCase() + crimeType.slice(1).toLowerCase() + '</label></li>');
		}		
	};

	gvizQuery.send(function(response) {
		var numRows = response.getDataTable().getNumberOfRows();
		for (var i = 0; i < numRows; i++) {
			var lat = response.getDataTable().getValue(i, 0);
			var lng = response.getDataTable().getValue(i, 1);
			var crime_type = response.getDataTable().getValue(i, 2);;
			var coordinate = new google.maps.LatLng(lat, lng);
			
			createCrimeMarker(coordinate, crime_type);
		}
	  
	
		$(".crimeSubType").click(function() {
			if ($(this).is(":checked")) {
				showOverlays(crimeMarkers[$(this).attr("name")]);
			} else {
				hideOverlays(crimeMarkers[$(this).attr("name")]);
			}
		});
	});	
	
	areaLayer = new google.maps.FusionTablesLayer({
		query: {
			select: 'poblacion',
			from: areaTableName
		}
	});
	
	areaLayer.setMap(map);
	
	
	var createTwitterMarker = function(coordinate) {
	   
		image = 'http://wwwimages.adobe.com/www.adobe.com/content/dam/Adobe/en/devnet/enterprise-platform/twitter/twitter_small_1307050985_2229.png.adimg.mw.58.png';

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
				createTwitterMarker(coordinate);
			});
		}
	);

	//heatLayer = new google.maps.FusionTablesLayer({	
	//	query: {
	//		select: 'geoname, longitude, latitude',
	//		from: crimeTableName,
	//	},
	//	heatmap: {
	//		enabled: true
	//	},
	//	style: {
	//		iconName: 'blu_blank'
	//	}
	//});
	//heatLayer.setMap(map);
	
	//AM: esto lo jode
	//applyStyle(map, areaLayer, $('#municipios_params select:first').find('option:selected').val());
}

google.maps.event.addDomListener(window, 'load', initialize);

function hideOverlays(markerCollection) {
	if (markerCollection) {
		for (i in markerCollection) {
			markerCollection[i].setMap(null);
		}
	}
}

function showOverlays(markerCollection) {
	if (markerCollection) {
		for (i in markerCollection) {
			markerCollection[i].setMap(map);
		}
	}
}

$(document).ready(function () {

	$("#area_layer").click(function() {
		areaLayer.setMap(($(this).is(":checked") ? map : null));
	});

	$("#heat_layer").click(function() {
		heatLayer.setMap(($(this).is(":checked") ? map : null));
	});
	
	$("#crime_layer").click(function() {
		if ($(this).is(":checked")) {
			$('#crime_sub_layers input[type="checkbox"]').each(function() {
				$(this).attr("checked", true).attr("disabled", false);
				showOverlays(crimeMarkers[$(this).attr("name")]);
			});			
		} else {
			$('#crime_sub_layers input[type="checkbox"]').each(function() {
				$(this).attr("checked", false).attr("disabled", true);
				hideOverlays(crimeMarkers[$(this).attr("name")]);
			});	
		}
	});
	
	queryTable(
		"SELECT nom_dep FROM TABLE GROUP BY nom_dep",
		areaTableName,
		apiKey,
		function(data) {
			if (populateSelect(parseJason(data), '#departamentos', '--- Todos ---')) {
				$('#departamentos').trigger('change');
			}
		}
	);
	
	$('#departamentos').change(function() {
		var selectedValue = $(this).find('option:selected').val();
		
		if (selectedValue != '') {
			queryTable(
				"SELECT nom_prov FROM TABLE WHERE nom_dep = '" + selectedValue + "' GROUP BY nom_prov",
				areaTableName,
				apiKey,
				function(data) {
					areaLayer.setOptions({
						query: {
							select: 'geometry',
							from: areaTableName,
							where: "nom_dep = '" + selectedValue + "'"
						}
					});
				}
			);
		} else {
			areaLayer.setOptions({
				query: {
					select: 'geometry',
					from: areaTableName,
				}
			});
		}
	});
	
}); //MT: end $(document).ready()
</script>

<div class="row-fluid">
	<div class="span2">
		<label for="crime_layer"><input type="checkbox" id="crime_layer" checked="checked"> Incidentes</label>
		<label for="area_layer"><input type="checkbox" id="area_layer" checked="checked"> Municipios</label>
		<br />
		<ul class="nav nav-tabs">
			<li class="active"><a href="#incidentes_pane" data-toggle="tab">Incidentes</a></li>
			<li><a href="#indicadores_pane" data-toggle="tab">Municipios</a></li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane active" id="incidentes_pane">
				<ul id="crime_sub_layers" class="noStyle"></ul>
			</div>
			<div class="tab-pane" id="indicadores_pane">
				<label for="map_departamentos">Departamentos:</label>
				<select name="map_departamentos" class="input-medium" id="departamentos"></select>
			</div>
		</div>
	</div><!--/span-->
	<div class="span8" style="height: 100%;">
		<div id="map_canvas"></div>
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

	
</script>
