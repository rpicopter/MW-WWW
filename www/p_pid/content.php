<div class="starter-template">
<p>Last updated: <span id="update_time"/></p>
<hr/>
<form class="form-horizontal">

<?php 
//generate form automatically to save us typing

$pids = array(
  "PIDROLL",
  "PIDPITCH",
  "PIDYAW",
  "PIDALT",
  "PIDPOS",
  "PIDPOSR",
  "PIDNAVR",
  "PIDLEVEL",
  "PIDMAG",
  "PIDVEL"
);

$len = count($pids);
for ($i=0;$i<$len;$i++) {
	$n = $pids[$i]; //this will be id
	$_n = strtolower($n);
	$l = ucfirst(substr($_n,3)); //this is label
?>
	<p class="lead"><?php echo $l; ?></p>
  	<div class="form-group">
    	<label for="<?php echo $n."_p" ?>" class="col-sm-2 control-label">P</label>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="<?php echo $n."_p" ?>" placeholder="<?php echo $l." P" ?>">
    	</div>
  	</div>
  	<div class="form-group">
    	<label for="<?php echo $n."_i" ?>" class="col-sm-2 control-label">I</label>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="<?php echo $n."_i" ?>" placeholder="<?php echo $l." I" ?>">
    	</div>
  	</div>
  	<div class="form-group">
    	<label for="<?php echo $n."_d" ?>" class="col-sm-2 control-label">D</label>
    	<div class="col-sm-10">
      		<input type="number" class="form-control" id="<?php echo $n."_d" ?>" placeholder="<?php echo $l." D" ?>">
    	</div> 
    </div> 
  	<hr/> 
<?php
}
?>

	<button id="submit_pid" type="button" class="btn btn-info">Set</button>
 </form>

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

    $("#submit_pid").click(
    	function() { save(); } 
    );
}

function start() {
	//console.log("Connected to mw proxy");
	var msg;

	msg = mw.filters([112]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );

	update();
	//setInterval(update,5000); //keep sending the requests every 5s
}

function update() {
	var msg;

	msg = mw.serialize({
		"id": 112
	});
	ws.send(msg);
	
}

function save() {
	//construct data
	var data = {
		"id": 202
	};
	for (var i=0;i<MultiWii.PID.length;i++) {
		data[ MultiWii.PID[i] ] = {
			"p": parseInt($("#"+MultiWii.PID[i]+"_p").val()),
			"i": parseInt($("#"+MultiWii.PID[i]+"_i").val()),
			"d": parseInt($("#"+MultiWii.PID[i]+"_d").val())
		};
	}

	var msg = mw.serialize(data);
	ws.send(msg);
}


function msg_pid(data) {
	for (var i=0;i<MultiWii.PID.length;i++) {
		var pid = data[ MultiWii.PID[i] ];

		$("#"+MultiWii.PID[i]+"_p").val(pid.p);
		$("#"+MultiWii.PID[i]+"_i").val(pid.i);
		$("#"+MultiWii.PID[i]+"_d").val(pid.d);
	}
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

