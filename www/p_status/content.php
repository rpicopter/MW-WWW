<div class="starter-template">
<p>Current time: <span id="current_time"/></p>
<p>Last updated: <span id="update_time"/></p>
<hr/>
<p class="lead">
	IDENT
</p>
<p class="llabel">Version:<span class="value" id="version"/></p>
<p class="llabel">Multitype:<span class="value" id="multitype"/></p>
<p class="llabel">MSP_Version:<span class="value" id="msp_version"/></p>
<p class="llabel">Capability:<span class="value" id="capability"/></p>
<hr/>
<p class="lead">
	STATUS
</p>
<p class="llabel">cycleTime:<span class="value" id="cycleTime"/></p>
<p class="llabel">i2c_errors_count:<span class="value" id="i2c_errors_count"/></p>
<p class="llabel">sensor:<span class="value" id="sensor"/></p>
<p class="llabel">flag:<span class="value" id="flag"/></p>
<p class="llabel">currentSet:<span class="value" id="currentSet"/></p>
</div>



<script type="text/javascript">
/* Page functions */
/* We need to define on_ready function that will connect to our mw proxy */
/* It will also install handlers (on) to tell us when the connection is established, message arrives etc */
function on_ready() {
	ws = new Websock();
        var lip = '<?php echo $host; ?>';
        ws.on('error',websock_err);
		ws.on('message',websock_recv);
		ws.on('open',start);
        ws.open("ws://"+lip+":8888");

    mw = new MultiWii();
}

function start() {
	console.log("Connected to mw proxy");
	var msg;

	msg = mw.filters([100,101]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );

	msg = mw.serialize({ //prepere a request message
		"id": 100
	});
	ws.send(msg); //send it

	setInterval(update,1000); //keep sending the requests every second
}

function websock_err() {
	console.log("Error: ",arguments);
}

function update() {
	var msg;
	$("#current_time").text(get_time()); 

	msg = mw.serialize({
		"id": 101
	});
	ws.send(msg);
	
}

function websock_recv() { //we have received a message
	var data;
	do { //receive messages in a loop to ensure we got all of them
		data = mw_recv();
		if (data.err == undefined) { //if err is set it means there was a genuine error or we haven't received enough data to proceed yet
			console.log("Received: ",data);
			///TODO: populate screen with data
		} else {
			//console.log(data.err);
		}

	} while (data.err == undefined); 
	
	
	$("#update_time").text(get_time()); 

}

</script>

