<div class="starter-template">
<p>Current time: <span id="current_time"/></p>
<p>Last updated: <span id="update_time"/></p>
<div id="info" class="alert alert-info">This page will feed RC info to the board every 200ms. 
	The rate is slow and might result in failsafe getting engaged. Also, some browsers might actually decrease the rate if this browser window gets minimized.</div>
<hr/>
<div>
  	<div class="form-group">
    	<p class="col-sm-2">Roll: <span class="value" id="roll"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="rollv"/>
    	</div>
  	</div>
  	<div class="form-group">
    	<p class="col-sm-2">Pitch: <span class="value" id="pitch"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="pitchv"/>
    	</div>
  	</div>
  	<div class="form-group">
    	<p class="col-sm-2">Yaw: <span class="value" id="yaw"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="yawv"/>
    	</div>
  	</div>
  	<div class="form-group">
    	<p class="col-sm-2">Thr: <span class="value" id="throttle"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="throttlev"/>
    	</div>
  	</div>
  	<div class="form-group">
    	<p class="col-sm-2">Aux1: <span class="value" id="aux1"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="aux1v"/>
    	</div>
  	</div>
  	<div class="form-group">
    	<p class="col-sm-2">Aux2: <span class="value" id="aux2"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="aux2v"/>
    	</div>
  	</div>
  	<div class="form-group">
    	<p class="col-sm-2">Aux3: <span class="value" id="aux3"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="aux3v"/>
    	</div>
  	</div>
  	<div class="form-group">
    	<p class="col-sm-2">Aux4: <span class="value" id="aux4"/></p>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="aux4v"/>
    	</div>
  	</div> 

</div>   
<div>
  <p>Feed RC</p>
  <input type="checkbox" id="feedrc" value=""/>
</div>

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

    firstMsg = false;
    feed = 0;
    value = [];


    $("#feedrc").click(
    	function() {
    		var checked = $('#feedrc:checked').val() != undefined;
    		if (checked) {
    			feed = 1;
    		} else {
    			feed = 0;
    		}
    	} 
    );    
}

function start() {
	//console.log("Connected to mw proxy");
	var msg;

	msg = mw.filters([105]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );

	requestRC();

	setInterval(update,200); //keep sending the requests every 200ms
	i=0;
	qw = 0;

}

function requestRC() {
	var msg;
 
	msg = mw.serialize({
		"id": 105
	});
	ws.send(msg);
}

function update() {
	if (!firstMsg) return;
	$("#current_time").text(get_time());

	requestRC();

	if (feed == 0) return;

	set_rc();

	var data = {
		"id": 200,
		"roll": value[0],
		"pitch": value[1],
		"yaw": value[2],
		"throttle": value[3],
		"aux1": value[4],
		"aux2": value[5],
		"aux3": value[6],
		"aux4": value[7]
	};	

	var msg  = mw.serialize(data);

	ws.send(msg);
	
}


function set_rc() {
	value = [
		parseInt($("#rollv").val()),
		parseInt($("#pitchv").val()),
		parseInt($("#yawv").val()),
		parseInt($("#throttlev").val()),
		parseInt($("#aux1v").val()),
		parseInt($("#aux2v").val()),
		parseInt($("#aux3v").val()),
		parseInt($("#aux4v").val())
	]
}

function msg_rc(data) {
	if (!firstMsg) {
		firstMsg = true;
		$("#rollv").val(data.roll); 
		$("#pitchv").val(data.pitch); 
		$("#yawv").val(data.yaw); 
		$("#throttlev").val(data.throttle); 
		$("#aux1v").val(data.aux1); 
		$("#aux2v").val(data.aux2); 
		$("#aux3v").val(data.aux3); 
		$("#aux4v").val(data.aux4); 
		value = [data.roll,data.pitch,data.yaw,data.throttle,data.aux1,data.aux2,data.aux3,data.aux4];
	}
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

