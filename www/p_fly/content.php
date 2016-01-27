<div class="starter-template">
<hr/>
<!--
<div id="start_info">
	<p class="lead">
	Please read carefully!
	<div id="warning" class="alert alert-warning">
		From the screen you are able to start and controll you copter. You will need to ensure your mobile will not go to sleep automatically. 
		Otherwise you might loose controll over your copter! Similarly you might want to disable autorotation of your screen.
	</div>
	<button id="accept_warning" type="button" class="btn btn-warning">I understand</button>
</p>
</div>
-->
<div id="rc">
  	<div class="form-group">
    	<label for="yaw" class="col-sm-2 control-label">Yaw</label>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="yaw" placeholder="Yaw">
    	</div>
  	</div>
  	<div class="form-group">
    	<label for="pitch" class="col-sm-2 control-label">Pitch</label>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="pitch" placeholder="Pitch">
    	</div>
  	</div>
  	<div class="form-group">
    	<label for="roll" class="col-sm-2 control-label">Roll</label>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="roll" placeholder="Roll">
    	</div> 
    </div> 
  	<div class="form-group">
    	<label for="throttle" class="col-sm-2 control-label">Throttle</label>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="throttle" placeholder="Throttle">
    	</div> 
    </div>     
    <button id="send" type="button" class="btn btn-primary">Send</button>
</div>
</div>



<script type="text/javascript">
/* Page functions */
/* We need to define on_ready function that will connect to our mw proxy */
/* It will also install handlers (on) to tell us when the connection is established, message arrives etc */

function on_ready() {
/*
	$("#accept_warning").click(
    	function() { 
    		$("#start_info").hide(); 
    		$("#setup").show(); 
    	} 
    );
*/

	$("#send").click(
    	function() { 
    		send_rc(); 
    	} 
    );

	ws = new Websock();
        ws.on('error',default_err);
		ws.on('message',websock_recv);
		ws.on('open',start);
        ws.open("ws://"+proxy_ip+":"+proxy_port);

    mw = new MultiWii();

    $("#throttle").val("1010");
    $("#yaw").val("1500");
    $("#pitch").val("1500");
    $("#roll").val("1500");
/*
    tiltSensor = false;
    initTiltSensor();

    updateCount = 0;
    interval_every_sec = setInterval(every_sec,1000); 
*/
}

function send_rc() {
	//construct data
	var data = {
		"id": 200,
		"roll": parseInt($("#roll").val()),
		"pitch": parseInt($("#pitch").val()),
		"yaw": parseInt($("#yaw").val()),
		"throttle": parseInt($("#throttle").val()),
		"aux1": 1500,
		"aux2": 1500,
		"aux3": 1500,
		"aux4": 1500
	};

	var msg = mw.serialize(data);
	ws.send(msg);
}

function initTiltSensor() {
// Create a new FULLTILT Promise for e.g. *compass*-based deviceorientation data
  var promise = new FULLTILT.getDeviceOrientation({ 'type': 'world' });

  promise
    .then(function(controller) {
    	tiltSensor = controller;
    	tiltSensor.start(orientation_updated);
    })
    .catch(function(message) {
      console.log(message);
      $("#danger").text("Error initiating device orientation (does your device and browser support it?)");
  	  $('#danger').show();
    });

}

function start() {
	console.log("Connected to mw proxy");
	var msg;
	msg = mw.filters([]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );
}

function compute_pitch(v) {
	//assume default pitch is 30deg
	//forward - decreases
	//max value-> 70deg
	//min value-> -10deg

	//we need to re-map this to 1000-2000
	v=Math.round(v);
	if (v>70) v=70;
	if (v<-10) v=-10;
	//center
	v-=30;
	//scale
	v/=40;
	v*=500;
	v+=1500;
	return v;
}

function compute_roll(v) {
	//assume default roll is 0deg
	//forward - decreases
	//max value-> 40deg
	//min value-> -40deg

	//we need to re-map this to 1000-2000
	v=Math.round(v);
	if (v>40) v=40;
	if (v<-40) v=-40;
	//scale
	v/=40;
	v*=500;
	v+=1500;
	return v;
}

function orientation_updated() {
	if (!tiltSensor) return;

      // Obtain the *screen-adjusted* normalized device rotation
      // as Quaternion, Rotation Matrix and Euler Angles objects
      // from our FULLTILT.DeviceOrientation object
      var quaternion = tiltSensor.getScreenAdjustedQuaternion();
      var matrix = tiltSensor.getScreenAdjustedMatrix();
      var euler = tiltSensor.getScreenAdjustedEuler();

      $("#alfa").text(Math.round(euler.alpha)); 
      $("#beta").text(Math.round(euler.beta)); 
      $("#gamma").text(Math.round(euler.gamma));

      //$("#pitch").text(compute_pitch(euler.beta)); 
      //$("#roll").text(compute_roll(euler.gamma));

      updateCount++;	
}

function every_sec() {
	if (updateCount==0) { //the orientation has not changed??
		$("#danger").text("It looks like your device (browser?) does not support orientation readings.");
  		$('#danger').show();
  		tiltSensor.stop();
  		clearInterval(interval_every_sec);
	}
     //$("#hz").text(updateCount); 
     updateCount = 0;


     //test
     	var msg;

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
			//console.log("Received: ",data);
			///populate screen with data
			switch (data.id) {
			}
		} else {
			//console.log(data);
		}

	} while (data.err == undefined); 

}

</script>

