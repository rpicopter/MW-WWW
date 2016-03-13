<div class="starter-template">
<p>Current time: <span id="current_time"/></p>
<p>Last updated: <span id="update_time"/></p>
<hr/>
<p class="llabel">GPS_FIX: <span class="value" id="gps_fix"/></p>
<p class="llabel">GPS_numSat: <span class="value" id="gps_numsat"/></p>
<p class="llabel">GPS_coord[LAT]: <span class="value" id="gps_coord_lat"/></p>
<p class="llabel">GPS_coord[LON]: <span class="value" id="gps_coord_lon"/></p>
<p class="llabel">GPS_altitude (m): <span class="value" id="gps_altitude"/></p>
<p class="llabel">GPS_speed (m): <span class="value" id="gps_speed"/></p>
<p class="llabel">GPS_ground_course (deg*10): <span class="value" id="gps_ground_course"/></p>
<p class="llabel">GPS_distanceToHome: <span class="value" id="GPS_distanceToHome"/></p>
<p class="llabel">computed home distance (m): <span class="value" id="home_distance"/></p>
<div id="map-container" style="height: 350px" class="row"></div>
<div class="row">
	<button id="get_home" type="button" class="btn btn-info">Check home</button>
</div>
</div>



<script type="text/javascript">
/* Page functions */
/* We need to define on_ready function that will connect to our mw proxy */
/* It will also install handlers (on) to tell us when the connection is established, message arrives etc */

function init_map(lat,lon) {
	var var_location = new google.maps.LatLng(lat,lon);

	var var_mapoptions = {
          center: var_location,
          zoom: 18
	};

	g_map = new google.maps.Map(document.getElementById("map-container"),
            var_mapoptions);
}

function on_ready() {

	//google.maps.event.addDomListener(window, 'load', init_map);
	g_map = null;
	init_map(0,0);

	ws = new Websock();
        ws.on('error',default_err);
		ws.on('message',websock_recv);
		ws.on('open',start);
        ws.open("ws://"+proxy_ip+":"+proxy_port);

    mw = new MultiWii();

    $("#get_home").click(
    	function() { request_wp(0); } 
    );     


  	marker_home = null;
  	location_home = null;
  	marker_current = null;
  	location_current = null;
}

function start() {
	//console.log("Connected to mw proxy");
	var msg;

	msg = mw.filters([106,118]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );

	counter = 0;
	setInterval(update,500); //keep sending the requests every 1s

	request_wp(0);
}

function update() {
	var msg;
	$("#current_time").text(get_time()); 

	if (counter==0) {
		msg = mw.serialize({
			"id": 106
		});
	} else {
		msg = mw.serialize({
			"id": 107
		});
	}

	counter++;
	if (counter==2) counter = 0;

	ws.send(msg);
}

function request_wp(i) {
	var msg;
		msg = mw.serialize({
			"id": 118,
			"wp_no": i
		});
	ws.send(msg);
}

function set_homelocation(lat,lon) {
	if (lat==0 && lon==0) {
		$("#info").text("Home position unknown. Ensure you have a fix. Calibrate Gyro or arm to reset home position.");
		$('#info').show();
		setTimeout(function(){$('#info').hide();},10000);
		return;
	}
	location_home = new google.maps.LatLng(lat,lon);

	if (marker_home) {
		marker_home.setPosition(location_home);
		g_map.panTo(location_home);
	} else {
		marker_home = new google.maps.Marker({
			position: location_home,
			map: g_map,
			icon: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png',
			title:"Home"});
 
 		marker_home.setMap(g_map);		
 		g_map.panTo(location_home);
	}
}

function msg_wp(data) {
	if (data.wp_no==0) set_homelocation(data.lat,data.lon);
	else console.log(data);
}

function set_currentlocation(lat,lon) {
	location_current = new google.maps.LatLng(lat,lon);

	if (marker_current) {
		marker_current.setPosition(location_current);
		if (!marker_home) g_map.panTo(location_current);
	} else {
		marker_current = new google.maps.Marker({
			position: location_current,
			map: g_map,
			title:"Current"});
 
 		marker_current.setMap(g_map);
 	}

}

function rad(x) {
  return x * Math.PI / 180;
};

function getDistance(p1, p2) {
  var R = 6378137; // Earth’s mean radius in meter
  var dLat = rad(p2.lat() - p1.lat());
  var dLong = rad(p2.lng() - p1.lng());
  var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(rad(p1.lat())) * Math.cos(rad(p2.lat())) *
    Math.sin(dLong / 2) * Math.sin(dLong / 2);
  var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  var d = R * c;
  return d; // returns the distance in meter
};

function calculate_home_distance() {
	if (!location_home || !location_current) return;
	var distance = getDistance(location_home, location_current);
	distance = Math.round(distance * 10)/10;
	$("#home_distance").text(distance);
}

function msg_gps(data) {
	set_currentlocation(data["gps_coord_lat"],data["gps_coord_lon"]);

	$("#gps_fix").text(data["gps_fix"]); 
	$("#gps_numsat").text(data["gps_numsat"]);
	$("#gps_coord_lat").text(data["gps_coord_lat"]);
	$("#gps_coord_lon").text(data["gps_coord_lon"]);
	$("#gps_altitude").text(data["gps_altitude"]);
	$("#gps_speed").text(data["gps_speed"]);
	$("#gps_ground_course").text(data["gps_ground_course"]);

}

function msg_comp_gps(data) {
	//console.log(data);
	$("#GPS_distanceToHome").text(data["GPS_distanceToHome"]);
}

function websock_recv() { //we have received a message
	var data;
	do { //receive messages in a loop to ensure we got all of them
		data = mw_recv();
		if (data.err == undefined) { //if err is set it means there was a genuine error or we haven't received enough data to proceed yet
			//console.log("Received: ",data);
			///populate screen with data
			switch (data.id) {
				case 106: msg_gps(data); break;
				case 107: msg_comp_gps(data); break;
				case 118: msg_wp(data); break;
			}
		} else {
			//console.log(data);
		}

	} while (data.err == undefined); 
	
	
	$("#update_time").text(get_time()); 

}

</script>

