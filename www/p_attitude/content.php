<div class="starter-template">
<p>Current time: <span id="current_time"/></p>
<p>Last updated: <span id="update_time"/></p>
<hr/>
	<p class="lead">
	Attitude	
	</p>
<p class="llabel">angx (units): <span class="value" id="angx"/></p>
<p class="llabel">angy (units): <span class="value" id="angy"/></p>
<p class="llabel">heading: <span class="value" id="heading"/></p>
<hr/>
	<p class="lead">
	Altitude
	</p>
<p class="llabel">Est Alt (cm): <span class="value" id="estalt"/></p>
<p class="llabel">vario (cm/s): <span class="value" id="vario"/></p>
<button id="calibrate_acc" type="button" class="btn btn-info">Calibrate Acc</button>
<button id="calibrate_mag" type="button" class="btn btn-info">Calibrate Mag</button>
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

    $("#calibrate_acc").click(
    	function() { calibrate_acc(); } 
    );
    $("#calibrate_mag").click(
    	function() { calibrate_mag(); } 
    );

    counter = 0;
}

function start() {
	//console.log("Connected to mw proxy");
	var msg;

	msg = mw.filters([108,109]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );

	setInterval(update,200); //keep sending the requests every 200ms

}

function update() {
	var msg;
	$("#current_time").text(get_time()); 

	if (counter==0) {
		msg = mw.serialize({
			"id": 108
		});
		counter++;
	} else {
		msg = mw.serialize({
			"id": 109
		});		
		counter=0;
	}

	ws.send(msg);


	
}

function calibrate_acc() {
	var msg;
	$("#current_time").text(get_time()); 

	msg = mw.serialize({
		"id": 205
	});
	ws.send(msg);

	$("#info").text("Wait a few seconds. Do not move your copter during this.");
	$('#info').show();
	setTimeout(function(){$('#info').hide();},10000);	
}

function calibrate_mag() {
	var msg;
	$("#current_time").text(get_time()); 

	msg = mw.serialize({
		"id": 206
	});
	ws.send(msg);
	$("#info").text("Rotate Copter on all 3 axes for 30 seconds");
	$('#info').show();
	setTimeout(function(){$('#info').hide();},30000);
}

function msg_attitude(data) {
	$("#angx").text(data.angx); 
	$("#angy").text(data.angy); 
	$("#heading").text(data.heading); 
}

function msg_altitude(data) {
	$("#estalt").text(data.EstAlt); 
	$("#vario").text(data.vario); 

}

function websock_recv() { //we have received a message
	var data;
	do { //receive messages in a loop to ensure we got all of them
		data = mw_recv();
		if (data.err == undefined) { //if err is set it means there was a genuine error or we haven't received enough data to proceed yet
			//console.log("Received: ",data);
			///populate screen with data
			switch (data.id) {
				case 108: msg_attitude(data); break;
				case 109: msg_altitude(data); break;
			}
		} else {
			//console.log(data);
		}

	} while (data.err == undefined); 
	
	
	$("#update_time").text(get_time()); 

}

</script>

