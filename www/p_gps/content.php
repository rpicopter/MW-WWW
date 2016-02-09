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
</div>



<script type="text/javascript">
/* Page functions */
/* We need to define on_ready function that will connect to our mw proxy */
/* It will also install handlers (on) to tell us when the connection is established, message arrives etc */
function on_ready() {
	ws = new Websock();
        ws.on('error',default_err);
		ws.on('message',websock_recv);
		ws.on('open',start);
        ws.open("ws://"+proxy_ip+":"+proxy_port);

    mw = new MultiWii();
}

function start() {
	//console.log("Connected to mw proxy");
	var msg;

	msg = mw.filters([106]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );

	setInterval(update,200); //keep sending the requests every 200ms

}

function update() {
	var msg;
	$("#current_time").text(get_time()); 

		msg = mw.serialize({
			"id": 106
		});

	ws.send(msg);


	
}

function msg_gps(data) {
	$("#gps_fix").text(data["gps_fix"]); 
	$("#gps_numsat").text(data["gps_numsat"]);
	$("#gps_coord_lat").text(data["gps_coord_lat"]);
	$("#gps_coord_lon").text(data["gps_coord_lon"]);
	$("#gps_altitude").text(data["gps_altitude"]);
	$("#gps_speed").text(data["gps_speed"]);
	$("#gps_ground_course").text(data["gps_ground_course"]);
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
			}
		} else {
			//console.log(data);
		}

	} while (data.err == undefined); 
	
	
	$("#update_time").text(get_time()); 

}

</script>

