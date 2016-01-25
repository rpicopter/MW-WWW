<div class="starter-template">
<p>Current time: <span id="current_time"/></p>
<p>Last updated: <span id="update_time"/></p>
<hr/>
<div>
  	<div class="form-group">
    	<p class="col-sm-2">motor1: <span class="value" id="motor1"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="motor1v">
    	</div>
  	</div>
  	<div class="form-group">
    	<p class="col-sm-2">motor2: <span class="value" id="motor2"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="motor2v">
    	</div>
  	</div>
  	<div class="form-group">
    	<p class="col-sm-2">motor3: <span class="value" id="motor3"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="motor3v">
    	</div>
  	</div>
  	<div class="form-group">
    	<p class="col-sm-2">motor4: <span class="value" id="motor4"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="motor4v">
    	</div>
  	</div>
  	<div class="form-group">
    	<p class="col-sm-2">motor5: <span class="value" id="motor5"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="motor5v">
    	</div>
  	</div>
  	<div class="form-group">
    	<p class="col-sm-2">motor6: <span class="value" id="motor6"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="motor6v">
    	</div>
  	</div>
  	<div class="form-group">
    	<p class="col-sm-2">motor7: <span class="value" id="motor7"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="motor7v">
    	</div>
  	</div>
  	<div class="form-group">
    	<p class="col-sm-2">motor8: <span class="value" id="motor8"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="motor8v">
    	</div>
  	</div>  	
</div>   
<button id="set_motors" type="button" class="btn btn-primary">Set</button>
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

    $("#set_motors").click(
    	function() { set_motors(); } 
    );

    firstMsg = false;
}

function start() {
	//console.log("Connected to mw proxy");
	var msg;

	msg = mw.filters([104]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );

	setInterval(update,200); //keep sending the requests every 200ms

}

function update() {
	var msg;
	$("#current_time").text(get_time()); 

	msg = mw.serialize({
		"id": 104
	});
	ws.send(msg);
	
}

function set_motors() {
	var msg;

	msg = mw.serialize({
		"id": 214,
		"motor1": $("#motor1v").val(),
		"motor2": $("#motor2v").val(),
		"motor3": $("#motor3v").val(),
		"motor4": $("#motor4v").val(),
		"motor5": $("#motor5v").val(),
		"motor6": $("#motor6v").val(),
		"motor7": $("#motor7v").val(),
		"motor8": $("#motor8v").val()
	});
	ws.send(msg);
}

function msg_motor(data) {
	if (!firstMsg) {
		firstMsg = true;
		$("#motor1v").val(data.motor1); 
		$("#motor2v").val(data.motor2); 
		$("#motor3v").val(data.motor3); 
		$("#motor4v").val(data.motor4); 
		$("#motor5v").val(data.motor5); 
		$("#motor6v").val(data.motor6); 
		$("#motor7v").val(data.motor7); 
		$("#motor8v").val(data.motor8);
	}
	$("#motor1").text(data.motor1); 
	$("#motor2").text(data.motor2); 
	$("#motor3").text(data.motor3); 
	$("#motor4").text(data.motor4); 
	$("#motor5").text(data.motor5); 
	$("#motor6").text(data.motor6); 
	$("#motor7").text(data.motor7); 
	$("#motor8").text(data.motor8); 
}

function websock_recv() { //we have received a message
	var data;
	do { //receive messages in a loop to ensure we got all of them
		data = mw_recv();
		if (data.err == undefined) { //if err is set it means there was a genuine error or we haven't received enough data to proceed yet
			//console.log("Received: ",data);
			///populate screen with data
			switch (data.id) {
				case 104: msg_motor(data); break;
			}
		} else {
			//console.log(data);
		}

	} while (data.err == undefined); 
	
	
	$("#update_time").text(get_time()); 

}

</script>

