<style type="text/css">
	#map_canvas { height: 500px; }
</style>
<script src="http://50.57.83.147:8084/socket.io/socket.io.js"></script>
<!-- 76489875 -->
<script type="text/javascript">
	
google.load('visualization', '1');
var drawingManager;
var map;
var areaTableName = '1IW43AblTgBE6OGz79hqMjq3yv_saG88OuE4pBJk';
var crimeTableName = '1S-QVAbfIrQKuT00Iw49x7ZXrRPNRYzHp1h92JkU';
var apiKey = 'AIzaSyB1EjUV_8Lmq6YkAQ04jwRttfGft94bXX0';

var crimeMarkers = [];
var twitterMarkers = [];
var userMarkers = [];
var smsMarkers = [];

var crimeLayer = null;
var heatLayer = null;
var areaLayer = null;

function initialize() {
	var lapaz = new google.maps.LatLng(-16.46463897, -68.14933062);
	var mapOptions = {
		center: lapaz,
		zoom: 11,
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
	
	
	var query = "SELECT latitude, longitude, crime_type, crime_id, crime_date, crime_time, crime_dep, crime_city, crime_zone, crime_st, crime_desc FROM " + crimeTableName;
	query = encodeURIComponent(query);
	var gvizQuery = new google.visualization.Query('http://www.google.com/fusiontables/gvizdata?tq=' + query);

	gvizQuery.send(function(response) {
		var numRows = response.getDataTable().getNumberOfRows();
		for (var i = 0; i < numRows; i++) {
			var lat = response.getDataTable().getValue(i, 0);
			var lng = response.getDataTable().getValue(i, 1);
			var crimeType = response.getDataTable().getValue(i, 2);
			var coordinate = new google.maps.LatLng(lat, lng);

			var crimeInfo = {
				crime_id: response.getDataTable().getValue(i, 3),
				crime_time: response.getDataTable().getValue(i, 4) + ', ' + response.getDataTable().getValue(i, 5),
				crime_city: response.getDataTable().getValue(i, 7) + ', ' + response.getDataTable().getValue(i, 6),
				crime_location: response.getDataTable().getValue(i, 9) + ', ' + response.getDataTable().getValue(i, 8),
				crime_description: response.getDataTable().getValue(i, 10)
			};
			createCrimeMarker(coordinate, crimeType, crimeInfo);
		}
	  
		$(".crimeSubType").click(function() {
			if ($(this).is(":checked")) {
				showOverlays(crimeMarkers[$(this).attr("name")]);
			} else {
				hideOverlays(crimeMarkers[$(this).attr("name")]);
			}
		});
	});	

	var createCrimeMarker = function(coordinate, crimeType, crimeInfo) {
		
		var crimeTypeId = crimeType;
		
		crimeTypeId = crimeTypeId.replace(/ /g, '_').replace(/-/g, '_').toLowerCase();
		
		iconImage = "<?php echo $this->Html->url('/img/', true); ?>map_" + crimeTypeId + ".png";

		var marker = new google.maps.Marker({
			map: map,
			position: coordinate,
			icon: new google.maps.MarkerImage(iconImage)
		});
		
		var infowindow = new google.maps.InfoWindow({
			content: "<h4>Caso #" + crimeInfo.crime_id + "   <span class='label label-info'>" + crimeType + "</span></h4>"
				+ "<p><strong>Hora / fecha:</strong> " + crimeInfo.crime_time + "</p>"
				+ "<p><strong>Ciudad:</strong> " + crimeInfo.crime_city + "</p>"
				+ "<p><strong>Lugar:</strong> " + crimeInfo.crime_location + "</p>"
				+ "<p><strong>Descripción:</strong></p>"
				+ "<p>" + crimeInfo.crime_description + "</p>"
		});
		
		google.maps.event.addListener(marker, 'click', function(event) {
			infowindow.open(map, marker);
		});
		
		if (typeof crimeMarkers[crimeTypeId] == "undefined") {
			crimeMarkers[crimeTypeId] = [];
		}
		crimeMarkers[crimeTypeId].push(marker);
		
		if ($("#sub_" + crimeTypeId).length  == 0) {
			$("#crime_sub_layers").append('<li id="sub_' + crimeTypeId + '"><label><input type="checkbox" class="crimeSubType"  name="' + crimeTypeId + '" checked="checked"> ' + crimeType.charAt(0).toUpperCase() + crimeType.slice(1).toLowerCase() + '</label></li>');
		}		
	};

	 
	$.getJSON("http://50.57.83.147:8083/tweets/?callback=?", 
		function(jsondata) {
			$.each(jsondata.results, function(i, item) {
				
				// SOLO TWEETS CON GEOLOCALIZACION
				
				//lat = (Math.random() * (16.5 - 16.49) + 16.49)*-1;
				//long = (Math.random() * (68.15 - 68.1) + 68.1)*-1;
				
				if(item.geo != null){
					if(item.geo.coordinates[0]!=0.000000){
						lat = item.geo.coordinates[0];
						long = item.geo.coordinates[1];
						var coordinate = new google.maps.LatLng(lat, long);
						
						var tweetInfo = {
							text: item.text,
							from_user: item.from_user,
							created_at: item.created_at,
							profile_image_url: item.profile_image_url
						}
						
						createTwitterMarker(coordinate, tweetInfo);
					}
				}
				

			});
		}
	);
	
	var createTwitterMarker = function(coordinate, tweetInfo) {
		
		iconImage = "<?php echo $this->Html->url('/img/map_twitter.png', true); ?>";
		
		var marker = new google.maps.Marker({
			map: map,
			position: coordinate,
			icon: new google.maps.MarkerImage(iconImage)
		});
		
		var infowindow = new google.maps.InfoWindow({
			content:
				'<img src="' + tweetInfo.profile_image_url + '" class="pull-left img-rounded">'
				+ '<div class="tweetText">'
				+ "<p><strong>" + tweetInfo.from_user + "</strong></p>"
				+ "<p>" + tweetInfo.text+ "</p>"
				+ "<small class=muted>" + tweetInfo.created_at + "</small>"
				+ "</div>"
		});
		
		google.maps.event.addListener(marker, 'click', function(event) {
			infowindow.open(map, marker);
		});
		
		twitterMarkers.push(marker);
	};
	
	function drawUserMarkers(){
		$.getJSON("<?php echo $this->Html->url('/',true)?>locations/get.json", 
			function(jsondata) {
				$.each(jsondata, function(i, item) {
					lat = (Math.random() * (16.5 - 16.49) + 16.49)*-1;
					long = (Math.random() * (68.15 - 68.1) + 68.1)*-1;
					
					if(typeof item.Location.lat !== 'undefined'){
						lat = item.Location.lat;
						long = item.Location.lon;
						var coordinate = new google.maps.LatLng(lat, long);
						
						var Info = {
							type: item.Location.type,
							place: item.Location.place,
							description: item.Location.description,
							date: item.Location.date
						}
						
						createUserMarker(coordinate, Info);
					}
	
				});
			}
		);
	}
	
	drawUserMarkers();
	
	var createUserMarker = function(coordinate, info) {
		
		iconImage = "<?php echo $this->Html->url('/img/map_user.png', true); ?>";
		
		var marker = new google.maps.Marker({
			map: map,
			position: coordinate,
			icon: new google.maps.MarkerImage(iconImage)
		});
		
		
		var infowindow = new google.maps.InfoWindow({
			content:
				' <div class="tweetText">'
				+ "<p><strong>" + info.type + "</strong></p>"
				+ "<p>" + info.place+ "</p>"
				+ "<p>" + info.date+ "</p>"
				+ "<small class=muted>" + info.description + "</small>"
				+ "</div>"
		});
		
		google.maps.event.addListener(marker, 'click', function(event) {
			infowindow.open(map, marker);
		});
		
		userMarkers.push(marker);
		
	};
	
	function drawSmsMarkers(){
		$.getJSON("<?php echo $this->Html->url('/',true)?>messages/get.json", 
			function(jsondata) {
				$.each(jsondata, function(i, item) {
					
					
					console.log(item);
					
					if(typeof item.Message.message!='undefined'){
						
						//randomico para probar concepto de SMS
						lat = (Math.random() * (16.5 - 16.49) + 16.49)*-1;
						long = (Math.random() * (68.15 - 68.1) + 68.1)*-1;
						
						var coordinate = new google.maps.LatLng(lat, long);
						
						var Info = {
							message: item.Message.message
						}
						
						createSmsMarker(coordinate, Info);
					}
	
				});
			}
		);
	}
	
	drawSmsMarkers();
	
	var createSmsMarker = function(coordinate, info) {
		
		iconImage = "<?php echo $this->Html->url('/img/map_sms.png', true); ?>";
		
		var marker = new google.maps.Marker({
			map: map,
			position: coordinate,
			icon: new google.maps.MarkerImage(iconImage)
		});
		
		
		var infowindow = new google.maps.InfoWindow({
			content:
				' <div class="tweetText">'
				+ "<p>" + info.message+ "</p>"
				+ "</div>"
		});
		
		google.maps.event.addListener(marker, 'click', function(event) {
			infowindow.open(map, marker);
		});
		
		smsMarkers.push(marker);
		
	};
	
	areaLayer = new google.maps.FusionTablesLayer({
		query: {
			select: 'poblacion',
			from: areaTableName
		}
	});
	
	google.maps.event.addListener(areaLayer, 'click', function(event) {
		event.infoWindowHtml =
			"<h5>" + event.row['nom_mun'].value + ', ' + event.row['nom_dep'].value + "</h5>"
			+ "<p><strong>Población:</strong> " + event.row['poblacion'].value + "</p>"
			+ "<p><strong>Esperanza de vida:</strong> " + event.row['ESP'].value + " años</p>"
			+ "<p><strong>Tasa de analfabetismo:</strong> " + event.row['TANA'].value + "%</p>"
			+ "<p><strong>Consumo per cápita:</strong> " + event.row['CPC'].value + "</p>"
			+ "<p><strong>Escolaridad:</strong> " + event.row['ESC'].value + "</p>"
		;
	})
	
	areaLayer.setMap(map);

	//add the drawing tool that allows users to draw points on the map
    drawingManager = new google.maps.drawing.DrawingManager({
        drawingMode: google.maps.drawing.OverlayType.MARKER,
        drawingControl: true,
        drawingControlOptions: {
            position: google.maps.ControlPosition.TOP_LEFT,
			
            drawingModes: [google.maps.drawing.OverlayType.MARKER]
        },
        markerOptions: {
            icon: new google.maps.MarkerImage('<?php echo $this->Html->url('/img/map_user.png', true); ?>'),
            draggable: true
        }
    });
	
	//add the tools to the map
    drawingManager.setMap(null);
		
	drawingManager.setOptions({
		drawingControl: false
	});

	
	google.maps.event.addListener(drawingManager, 'overlaycomplete', function (point)
    {
        //"clone" the save-form to put in the infowindow
        var form =    $(".save-form").clone().show();
        var infowindow_content = form[0];
        var infowindow = new google.maps.InfoWindow({
            content: infowindow_content
        });

        google.maps.event.addListener(point.overlay, 'click', function() {
            infowindow.open(map,point.overlay);
        });

        //open infowindow by default
        infowindow.open(map,point.overlay);
		

        //when user clicks on the "submit" button
        form.submit({point: point}, function (event) {
            //prevent the default form behavior (which would refresh the page)
            event.preventDefault();

            //put all form elements in a "data" object
            var data = {
                type: $("select[name=type]",this).val(),
                place: $("select[name=place]",this).val(),
				description: $("textarea[name=description]", this).val(),
                date: $("input[name=date]",this).val(),
                lat: event.data.point.overlay.getPosition().lat(),
                lon: event.data.point.overlay.getPosition().lng()
            };
            //trace(data)

            //send the results to the PHP script that adds the point to the database
            $.post("locations/add", data,
				function (data) {
					console.log(data);
					/*
					if (typeof data.error != "undefined")
					{
						alert(data.error);
					}
					else 
					{
						alert(data.message);
					} */
				},
			"json");

            //Erase the form and replace with new message
            infowindow.setContent('Incidente reportado. Sera validado por uno de nuestros operadores')
            return false;
        });
		
    });
	
	
	
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

	var createSmsM = function(coordinate, info) {
		
		iconImage = "<?php echo $this->Html->url('/img/map_sms.png', true); ?>";
		
		var marker = new google.maps.Marker({
			map: map,
			position: coordinate,
			icon: new google.maps.MarkerImage(iconImage)
		});
		
		
		var infowindow = new google.maps.InfoWindow({
			content:
				' <div class="tweetText">'
				+ "<p>" + info.message+ "</p>"
				+ "</div>"
		});
		
		google.maps.event.addListener(marker, 'click', function(event) {
			infowindow.open(map, marker);
		});
		
		smsMarkers.push(marker);
		
	};
	
	var socket = io.connect('http://50.57.83.147:8084');
	socket.on('smsweb', function (data) {
		console.log(data);
		lat = (Math.random() * (16.5 - 16.49) + 16.49)*-1;
		long = (Math.random() * (68.15 - 68.1) + 68.1)*-1;
		var coordinate = new google.maps.LatLng(lat, long);
		var Info = {
					message: data.message
				}
		createSmsM(coordinate, Info);
	});
  
	/*$('#date').datepicker({
		format: 'dd-mm-yyyy'
	});*/
			
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
	
	$("#twitter_layer").click(function() {
		if ($(this).is(":checked")) {
			showOverlays(twitterMarkers);
		} else {
			hideOverlays(twitterMarkers);
		}
	});
	
	$("#user_layer").click(function() {
		if ($(this).is(":checked")) {
			showOverlays(userMarkers);
		} else {
			hideOverlays(userMarkers);
		}
	});
	
	$("#sms_layer").click(function() {
		if ($(this).is(":checked")) {
			showOverlays(smsMarkers);
		} else {
			hideOverlays(smsMarkers);
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

	
	$('#municipios_params select:first').change(function() {
		var selectedValue = $(this).find('option:selected').val();
		
		if (selectedValue != '') {
			applyStyle(map, areaLayer, selectedValue);
			$(".mapLegend").hide();
			$("#legend_" + selectedValue).show();
		}
	});
	
	$("#add_incidente").click(function() {
		drawingManager.setOptions({
		  drawingControl: true
		});
		drawingManager.setMap(map);
		
		$("#date").datepicker();

		//infowindow = new google.maps.InfoWindow();
		//infowindow.setContent("prueba sss");
		//infowindow.open(map, map.getCenter());
	});
	
	$('#city').change(function() {
		var selectedValue = $(this).find('option:selected').val();
		
		if (selectedValue == 'Cochabamba') {
			map.setCenter(new google.maps.LatLng( -17.384716084990472, -66.16741561330855 ) );
		}else if (selectedValue == 'Cobija') {
			map.setCenter(new google.maps.LatLng( -11.029831069146613, -68.76043510157615 ) );
		}
		else if (selectedValue == 'El Alto') {
			map.setCenter(new google.maps.LatLng( -16.5081869804345, -68.1695766421035 ) );
		}
		else if (selectedValue == 'La Paz') {
			map.setCenter(new google.maps.LatLng(-16.492715338879044, -68.17997359670699 ) );
		}
		else if (selectedValue == 'Oruro') {
			map.setCenter(new google.maps.LatLng(-17.98918266463051, -67.11154178716242 ) );
		}
		else if (selectedValue == 'Potosi') {
			map.setCenter(new google.maps.LatLng(-19.575317892869453, -65.77670291997492 ) );
		}
		else if (selectedValue == 'Santa Cruz') {
			map.setCenter(new google.maps.LatLng(-17.790535393588964, -63.186882028821856 ) );
		}
		else if (selectedValue == 'Sucre') {
			map.setCenter(new google.maps.LatLng(-19.051733665039155, -65.27055360842496 ) );
		}
		else if (selectedValue == 'Tarija') {
			map.setCenter(new google.maps.LatLng(-21.51440672003028, -64.72673036623746 ) );
		}
		else if (selectedValue == 'Tarija') {
			map.setCenter(new google.maps.LatLng(-21.51440672003028, -64.72673036623746 ) );
		}
		else if (selectedValue == 'Trinidad') {
			map.setCenter(new google.maps.LatLng(-14.833965277394848, -64.90742111694999 ) );
		}
	});
	
}); //MT: end $(document).ready()
</script>


<div class="row-fluid">
	
	<div class="span10">
		<form class="form-inline">
			<label class="select">
			Pais:
			</label>
			<select id="country" name="country">
				<option selected>Bolivia</option>
			</select>
			<label class="select">
			Ciudad:
			</label>
			<select id="city" name="city">
				<option>Cobija</option>
				<option>Cochabamba</option>
				<option>El Alto</option>
				<option selected>La Paz</option
				<option>Oruro</option>
				<option>Potosi</option>
				<option>Santa Cruz</option>
				<option>Sucre</option>
				<option>Tarija</option>
				<option>Trinidad</option>
			</select>
			<span class="alert alert-warning inline">
				<small>Pronto nuevos paises y ciudades.</small>
			</span>
		</form>
	</div>
</div>

<div class="row-fluid">
	<div class="span2">
		<label><input type="checkbox" id="crime_layer" checked="checked"> Incidentes oficiales</label>
		<label><input type="checkbox" id="twitter_layer" checked="checked"> Tweets</label>
		<label><input type="checkbox" id="user_layer" checked="checked"> Incidentes por usuarios</label>
		<label><input type="checkbox" id="sms_layer" checked="checked"> Incidentes desde SMS</label>
		<label><input type="checkbox" id="area_layer" checked="checked"> Municipios</label>		
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
				<div id="municipios_params">
					<label for="map_municipios_params">Municipios:</label>
					<select name="map_municipios_params" class="input-medium">
						<option value="poblacion" selected="selected">Población</option>
						<option value="ESP">Esperanza de vida</option>
						<option value="TANA">Tasa de analfabetismo</option>
						<option value="CPC">Consumo per cápita</option>
					</select>					
				</div>
				<div id="municipios_legends" class="mapLegendContainer well"></div>
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

<form class="form-horizontal save-form" style="display: none">
  <div class="control-group">
    <label class="control-label" for="inputTipo">Tipo:</label>
    <div class="controls">
		<select id="type" name="type">
			<option>HECHO DE TRANSITO</option>
			<option>ROBO-HURTO</option>
			<option>AGRESIONES-LESIONES</option>
			<option>HOMICIDIO</option>
			<option>ROBO CON ARMA</option>
			<option>INCENDIO</option>
			<option>SECUESTRO</option>
			<option>PERSONA DESAPARECIDA</option>
			<option>TRAFICO O VENTA DE DROGAS</option>
			<option>SUICIDIO</option>
		</select>
    </div>
  </div>
  <div class="control-group">
    <label class="control-label" for="inputTipo">Lugar:</label>
    <div class="controls">
		<select id="place" name="place">
			<option>ESCUELA</option>
			<option>ESPACIO PUBLICO</option>
			<option>UNIVERSIDAD</option>
			<option>HOSPITAL</option>
			<option>ESPACIOS PUBLICOS</option>
			<option>HOGAR</option>
		</select>
    </div>
  </div>
  <div class="control-group">
    <label class="control-label" for="inputTipo">Descripcion:</label>
    <div class="controls">
		<textarea id="description" name="description"></textarea>
    </div>
  </div>
  <div class="control-group">
    <label class="control-label" for="inputTipo">Fecha:</label>
    <div class="controls">
		<input type="text" id="date" name="date" value="08-12-2012" />
	</div>
  </div>
  <div class="control-group">
    <div class="controls">
      <button type="submit" class="btn">Guardar</button>
    </div>
  </div>
</form>

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
	'ESP': [
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
	'TANA': [
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
	'CPC': [
		{
			'min': 0,
			'max': 400,
			'color': '#E5D4DD'
		},
		{
			'min': 400,
			'max': 800,
			'color': '#CCAABB'
		},
		{
			'min': 800,
			'max': 1200,
			'color': '#B27F99'
		},
		{
			'min': 1200,
			'max': 1600,
			'color': '#995577'
		},
		{
			'min': 1600,
			'max': 2000,
			'color': '#7F2A55'
		},
		{
			'min': 2000,
			'max': 2400,
			'color': '#660033'
		}
	],
}

for (var i in layerStyles) {
	var listItems = '<ul id="legend_' + i + '" class="mapLegend noStyle">';
	listItems += '<li class="legendTitle">' + i + '</li>';
	for(var style in layerStyles[i]) {
		listItems += '<li><span class="legendColor" style="background-color: ' + layerStyles[i][style].color + ';"></span> ' + layerStyles[i][style].min + ' - ' + layerStyles[i][style].max + '</li>';
	}
	listItems += '</ul>';
	$("#municipios_legends").append(listItems);
}

$(".mapLegend").hide();
$("#legend_" + $('#municipios_params select:first').find('option:selected').val()).show();

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
