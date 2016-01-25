<div class="starter-template">
<p>Current time: <span id="current_time"/></p>
<p>Last updated: <span id="update_time"/></p>
<hr/>
<p class="llabel">Roll: <span class="value" id="roll"/></p>
<p class="llabel">Pitch: <span class="value" id="pitch"/></p>
<p class="llabel">Yaw: <span class="value" id="yaw"/></p>
<p class="llabel">Throttle: <span class="value" id="throttle"/></p>
<p class="llabel">Aux1: <span class="value" id="aux1"/></p>
<p class="llabel">Aux2: <span class="value" id="aux2"/></p>
<p class="llabel">Aux3: <span class="value" id="aux3"/></p>
<p class="llabel">Aux4: <span class="value" id="aux4"/></p>
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

	msg = mw.filters([105]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );

	setInterval(update,200); //keep sending the requests every 200ms

}

function update() {
	var msg;
	$("#current_time").text(get_time()); 

	msg = mw.serialize({
		"id": 105
	});
	ws.send(msg);
	
}

function msg_rc(data) {
	$("#roll").text(data.roll); 
	$("#pitch").text(data.pitch); 
	$("#yaw").text(data.yaw); 
	$("#throttle").text(data.throttle); 
	$("#aux1").text(data.aux1); 
	$("#aux2").text(data.aux2); 
	$("#aux3").text(data.aux3); 
	$("#aux4").text(data.aux4); 
}

function websock_recv() { //we have received a message
	var data;
	do { //receive messages in a loop to ensure we got all of them
		data = mw_recv();
		if (data.err == undefined) { //if err is set it means there was a genuine error or we haven't received enough data to proceed yet
			//console.log("Received: ",data);
			///populate screen with data
			switch (data.id) {
				case 105: msg_rc(data); break;
			}
		} else {
			//console.log(data);
		}

	} while (data.err == undefined); 
	
	
	$("#update_time").text(get_time()); 

}

</script>

