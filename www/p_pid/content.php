<div class="starter-template">
<p>Current time: <span id="current_time"/></p>
<p>Last updated: <span id="update_time"/></p>
<hr/>
<p class="lead">
	ATTITUDE
</p>
<p class="llabel">angx (units): <span class="value" id="angx"/></p>
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

	msg = mw.filters([112]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );

	update();
	setInterval(update,5000); //keep sending the requests every 5s
}

function update() {
	var msg;
	$("#current_time").text(get_time()); 

	msg = mw.serialize({
		"id": 112
	});
	ws.send(msg);
	
}


function msg_pid(data) {
	console.log(data);
	
}

function websock_recv() { //we have received a message
	var data;
	do { //receive messages in a loop to ensure we got all of them
		data = mw_recv();
		if (data.err == undefined) { //if err is set it means there was a genuine error or we haven't received enough data to proceed yet
			//console.log("Received: ",data);
			///populate screen with data
			switch (data.id) {
				case 112: msg_pid(data); break;
			}
		} else {
			//console.log(data);
		}

	} while (data.err == undefined); 
	
	
	$("#update_time").text(get_time()); 

}

</script>

