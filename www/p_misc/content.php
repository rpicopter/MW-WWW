<div class="starter-template">
<p>Current time: <span id="current_time"/></p>
<p>Last updated: <span id="update_time"/></p>
<hr/>
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

	msg = mw.filters([114]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );

	setInterval(update,1000); //keep sending the requests every 200ms

}

function update() {
	var msg;
	$("#current_time").text(get_time()); 

	msg = mw.serialize({
		"id": 114
	});
	ws.send(msg);
	
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
				case 114: msg_misc(data); break;
			}
		} else {
			//console.log(data);
		}

	} while (data.err == undefined); 
	
	
	$("#update_time").text(get_time()); 

}

</script>

