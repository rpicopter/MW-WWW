<div class="starter-template">
<p>Last updated: <span id="update_time"/></p>
<button id="reset_conf" type="button" class="btn btn-info">Reset params</button>
<button id="reset_mw" type="button" class="btn btn-info">Reset MultiWii</button>
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
<hr/>
<p class="lead">
	ANALOG
</p>
<p class="llabel">vbat (1/10 V): <span class="value" id="vbat"/></p>
<p class="llabel">intPowerMeterSum: <span class="value" id="intPowerMeterSum"/></p>
<p class="llabel">rssi [1-1023]: <span class="value" id="rssi"/></p>
<p class="llabel">amperage: <span class="value" id="amperage"/></p>
<hr/>
<p class="lead">
	MISC
</p>
<p class="llabel">intPowerTrigger1: <span class="value" id="intPowerTrigger1"/></p>
<p class="llabel">conf.minthrottle: <span class="value" id="conf_minthrottle"/></p>
<p class="llabel">MAXTHROTTLE: <span class="value" id="maxthrottle"/></p>
<p class="llabel">MINCOMMAND: <span class="value" id="mincommand"/></p>
<p class="llabel">conf.failsafe_throttle: <span class="value" id="conf_failsafe_throttle"/></p>
<p class="llabel">plog.arm: <span class="value" id="plog_arm"/></p>
<p class="llabel">plog.lifetime: <span class="value" id="plog_lifetime"/></p>
<p class="llabel">conf.mag_declination: <span class="value" id="conf_mag_declination"/></p>
<p class="llabel">conf.vbatscale: <span class="value" id="conf_vbatscale"/></p>
<p class="llabel">conf.vbatlevel_warn1: <span class="value" id="conf_vbatlevel_warn1"/></p>
<p class="llabel">conf.vbatlevel_warn2: <span class="value" id="conf_vbatlevel_warn2"/></p>
<p class="llabel">conf.vbatlevel_crit: <span class="value" id="conf_vbatlevel_crit"/></p>
<hr/>
<p class="lead">
	SERVICE STATUS
</p>
<p class="llabel">link rssi: <span class="value" id="link_rssi"/></p>
<p class="llabel">uart_errors_count: <span class="value" id="uart_errors_count"/></p>
<p class="llabel">uart_tx_count: <span class="value" id="uart_tx_count"/></p>
<p class="llabel">uart_rx_count: <span class="value" id="uart_rx_count"/></p>
<p class="llabel">uart_tx_rate (msg/s): <span class="value" id="uart_tx_rate"/></p>
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

    $("#reset_mw").click(
    	function() { reset_mw(); } 
    );

    counter = 0;
    ms_prev = new Date().getTime(); //used to calculate tx-, rx-rate
    msg_prev = 0;
}

function reset_all() {
	var msg;

	msg = mw.serialize({
		"id": 208
	});
	ws.send(msg);
	
}


function reset_mw() {
	var msg;

	msg = mw.serialize({
		"id": 51
	});
	ws.send(msg);
	
	$("#info").text("Wait a few seconds. If it does not work ensure you have connected your respective host GPIO with reset pin on MW board");
	$('#info').show();
	setTimeout(function(){$('#info').hide();},10000);		
}

function start() {
	//console.log("Connected to mw proxy");
	var msg;

	msg = mw.filters([50,100,101,110,114]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );

	msg = mw.serialize({ //prepere a request message
		"id": 100
	});
	ws.send(msg); //send it

	setInterval(update,250); //keep sending the requests every second
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

	if (counter==2) {
		msg = mw.serialize({
			"id": 110
		});
		ws.send(msg);
	} 

	if (counter==3) {
		msg = mw.serialize({
			"id": 114
		});
		ws.send(msg);
	} 

	counter++;
	if (counter==4) counter = 0;
}

function lmsg_status(data) {
	var delta = 0;
	var ms_now = new Date().getTime();
	delta = ms_now-ms_prev;

	ms_prev = ms_now;
	var tx_e = data.uart_tx_count;
	var rx_e = data.uart_rx_count;
	var crc_e = data.uart_errors_count;
	if (msg_prev==0) msg_prev = tx_e;
	var tx_rate = (tx_e-msg_prev)/(delta/1000);
	tx_rate = Math.round(tx_rate*10)/10;
	msg_prev = tx_e;

	$("#uart_rx_count").text(rx_e); 
	$("#uart_tx_count").text(tx_e); 
	$("#uart_errors_count").text(crc_e + "("+(crc_e/rx_e*100).toFixed(3)+"%)");  
	$("#link_rssi").text(data.link_rssi); 
	$("#uart_tx_rate").text(tx_rate); 
	
}

function msg_ident(data) {
	$("#version").text(data.version); 
	$("#msp_version").text(data.msp_version); 
	$("#capability").text(JSON.stringify(data.capability)); 
	$("#multitype").text(MultiWii.MultiType[data.multitype]); 
}

function msg_analog(data) {
	$("#vbat").text(data.cycleTime); 
	$("#intPowerMeterSum").text(data.intPowerMeterSum); 
	$("#rssi").text(JSON.stringify(data.rssi)); 
	$("#amperage").text(data.amperage); 
}

function msg_status(data) {
	$("#cycleTime").text(data.cycleTime); 
	$("#i2c_errors_count").text(data.i2c_errors_count); 
	$("#sensor").text(JSON.stringify(data.sensor)); 
	$("#flag").text(data.flag); 
	$("#currentSet").text(data["global_conf.currentSet"]); 
}

function msg_misc(data) {
	$("#intPowerTrigger1").text(data.intPowerTrigger1); 
	$("#conf_minthrottle").text(data["conf.minthrottle"]); 
	$("#maxthrottle").text(data.maxthrottle); 
	$("#mincommand").text(data.mincommand); 
	$("#conf_failsafe_throttle").text(data["conf.failsafe_throttle"]); 
	$("#plog_arm").text(data["plog.arm"]); 
	$("#plog_lifetime").text(data["plog.lifetime"]); 
	$("#conf_mag_declination").text(data["conf.mag_declination"]); 
	$("#conf_vbatscale").text(data["conf.vbatscale"]); 
	$("#conf_vbatlevel_warn1").text(data["conf.vbatlevel_warn1"]); 
	$("#conf_vbatlevel_warn2").text(data["conf.vbatlevel_warn2"]); 
	$("#conf_vbatlevel_crit").text(data["conf.vbatlevel_crit"]); 
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
				case 110: msg_analog(data); break;
				case 114: msg_misc(data); break;
			}
		} else {
			//console.log(data);
		}

	} while (data.err == undefined); 
	
	
	$("#update_time").text(get_time()); 

}

</script>

