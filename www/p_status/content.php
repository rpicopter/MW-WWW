<div class="starter-template">
<p>Last updated: <span id="update_time"/></p>
<hr/>
<p class="lead">
	IDENT
</p>
<p class="llabel">Version: <span class="value" id="version"/></p>
<p class="llabel">Multitype: <span class="value" id="multitype"/></p>
<p class="llabel">MSP_Version: <span class="value" id="msp_version"/></p>
<p class="llabel">Capability: <span class="value" id="capability"/></p>
<hr/>
<p class="lead">
	STATUS
</p>
<p class="llabel">cycleTime (micros): <span class="value" id="cycleTime"/></p>
<p class="llabel">i2c_errors_count: <span class="value" id="i2c_errors_count"/></p>
<p class="llabel">sensor: <span class="value" id="sensor"/></p>
<p class="llabel">flag: <span class="value" id="flag"/></p>
<p class="llabel">currentSet: <span class="value" id="currentSet"/></p>
<button id="reset_conf" type="button" class="btn btn-info">Reset all</button>
<p class="lead">
	SERVICE STATUS
</p>
<p class="llabel">uart_errors_count: <span class="value" id="uart_errors_count"/></p>
<p class="llabel">uart_tx_count: <span class="value" id="uart_tx_count"/></p>
<p class="llabel">uart_rx_count: <span class="value" id="uart_rx_count"/></p>
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

    $("#reset_conf").click(
    	function() { reset_all(); } 
    );

    counter = 0;
}

function reset_all() {
	var msg;

	msg = mw.serialize({
		"id": 208
	});
	ws.send(msg);
	
}

function start() {
	//console.log("Connected to mw proxy");
	var msg;

	msg = mw.filters([50,100,101]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );

	msg = mw.serialize({ //prepere a request message
		"id": 100
	});
	ws.send(msg); //send it

	setInterval(update,500); //keep sending the requests every second
}

function update() {
	var msg;

	if (counter==0) {
		msg = mw.serialize({
			"id": 101
		});
		ws.send(msg);
	} 

	if (counter==1) {
		msg = mw.serialize({
			"id": 50
		});
		ws.send(msg);
	} 


	counter++;
	if (counter==2) counter = 0;
}

function lmsg_status(data) {
	var tx_e = data.uart_tx_count;
	var rx_e = data.uart_rx_count;
	var crc_e = data.uart_errors_count;

	$("#uart_rx_count").text(rx_e); 
	$("#uart_tx_count").text(tx_e);  
	$("#uart_errors_count").text(crc_e + "("+(crc_e/rx_e*100).toFixed(3)+"%)");  
	
}

function msg_ident(data) {
	$("#version").text(data.version); 
	$("#msp_version").text(data.msp_version); 
	$("#capability").text(JSON.stringify(data.capability)); 
	$("#multitype").text(MultiWii.MultiType[data.multitype]); 
}

function msg_status(data) {
	$("#cycleTime").text(data.cycleTime); 
	$("#i2c_errors_count").text(data.i2c_errors_count); 
	$("#sensor").text(JSON.stringify(data.sensor)); 
	$("#flag").text(data.flag); 
	$("#currentSet").text(data["global_conf.currentSet"]); 
}

function websock_recv() { //we have received a message
	var data;
	do { //receive messages in a loop to ensure we got all of them
		data = mw_recv();
		if (data.err == undefined) { //if err is set it means there was a genuine error or we haven't received enough data to proceed yet
			//console.log("Received: ",data);
			///populate screen with data
			switch (data.id) {
				case 50: lmsg_status(data); break;
				case 100: msg_ident(data); break;
				case 101: msg_status(data); break;
			}
		} else {
			//console.log(data);
		}

	} while (data.err == undefined); 
	
	
	$("#update_time").text(get_time()); 

}

</script>

