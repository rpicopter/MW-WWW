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

function on_ready() {
	ws = new Websock();
        var lip = '<?php echo $host; ?>';
        console.log(lip);
        ws.on('error',websock_err);
	ws.on('message',websock_recv)
        ws.open("ws://"+lip+":8888");

	mw_send(100,[]);

	setInterval(send_request,1000);

}

function websock_err() {
	console.log("Error: ",arguments);
}

function send_request() {
	$("#current_time").text(get_time()); 


	mw_send(101,[]);
}

function websock_recv() {

        var data = msp_recv();
        if (data.err !== undefined) {
		websock_err(data.err);
                return;
        }
        if (data.msg) {
		websock_err(data.err);
                return;
        }


	$("#update_time").text(get_time()); 

	///TODO: parse and populate
	console.log("Received: "+data);

}

</script>

