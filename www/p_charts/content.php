<div class="starter-template">
<p>Last updated: <span id="update_time"/></p>
<hr/>
<p class="lead">
	CHARTS
</p>
		<p class="llabel">Update rate: <span class="value" id="rate"/></p>

		<div id="charttype">
			<div class="radio radio-inline" id="optaccelw">
			  <label><input type="radio" name="optradio" id="optaccel">ACCEL</label>
			</div>
			<div class="radio radio-inline" id="optgyrow">
			  <label><input type="radio" name="optradio" id="optgyro">GYRO</label>
			</div>
			<div class="radio radio-inline" id="optmagw">
			  <label><input type="radio" name="optradio" id="optmag">MAG</label>
			</div>
			<div class="radio radio-inline" id="optmotorw">
			  <label><input type="radio" name="optradio" id="optmotor">MOTOR</label>
			</div>
		</div>
		<hr>
				
		<div style="display: hidden;" id="accel">
			<div style="height: 400px;" id="chart_a"></div>
			<p class="llabel">accx: <span class="value" id="accx"/></p>
			<p class="llabel">accy: <span class="value" id="accy"/></p>
			<p class="llabel">accz: <span class="value" id="accz"/></p>					
		</div>
		
		<div style="display: hidden;" id="gyro">
			<div style="height: 400px;" id="chart_g"></div>
			<p class="llabel">gyrx: <span class="value" id="gyrx"/></p>
			<p class="llabel">gyry: <span class="value" id="gyry"/></p>
			<p class="llabel">gyrz: <span class="value" id="gyrz"/></p>			
		</div>

		<div style="display: hidden;" id="mag">
			<div style="height: 400px;" id="chart_m"></div>		
			<p class="llabel">magx: <span class="value" id="magx"/></p>
			<p class="llabel">magy: <span class="value" id="magy"/></p>
			<p class="llabel">magz: <span class="value" id="magz"/></p>
		</div>

		<div style="display: hidden;" id="motor">
			<div style="height: 600px;" id="chart_motor"></div>		
			<p class="llabel">motor1: <span class="value" id="motor1"/></p>
			<p class="llabel">motor2: <span class="value" id="motor2"/></p>
			<p class="llabel">motor3: <span class="value" id="motor3"/></p>
			<p class="llabel">motor4: <span class="value" id="motor4"/></p>
			<p class="llabel">motor5: <span class="value" id="motor5"/></p>
			<p class="llabel">motor6: <span class="value" id="motor6"/></p>
			<p class="llabel">motor7: <span class="value" id="motor7"/></p>
			<p class="llabel">motor8: <span class="value" id="motor8"/></p>					
		</div>

<hr/>
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

    counter=0;

    sensor = null;

    msgid = 0;

    charttype = null;

	dataLength = 500;
	xVal = 0;
	dps_g = [[],[],[]];
	dps_m = [[],[],[]]; 
	dps_a = [[],[],[]]; 
	dps_motor = [[],[],[],[]];    
}


function start() {
	//console.log("Connected to mw proxy");
	var msg;
	initialized = 0;

	msg = mw.filters([101,102, 104]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );

	function req_status() {
		var msg = mw.serialize({"id": 101}); //request status to find out which charts we need
		ws.send(msg);
	}

	req_status();
	ts = setInterval(req_status,1000);
}

function init_with_status(data) {
	if (initialized) return;
	initialized = 1;

	clearInterval(ts);

	sensor = data.sensor;

	//we always have gyroscope
	//$("#optgyrow").removeClass("disabled");
	//$("#optmotor").removeClass("disabled");
	

	if (data.sensor.acc) {
		//$("#optaccelw").removeClass("disabled");
	}
	if (data.sensor.mag) {
		//$("#optmagw").removeClass("disabled");
	}

	setInterval(update,50); //keep sending the requests every 200ms
	setInterval(update_rate,1000); //keep sending the requests every 200ms
}

function update_rate() {
	$("#rate").text(counter);
	counter = 0;
}

function update() {
	var _charttype = $('#charttype input:radio:checked')[0];
	if (!_charttype) return;
	if (charttype != _charttype.id) {
		charttype = _charttype.id;
		$("#accel").hide();
		$("#gyro").hide();
		$("#mag").hide();
		$("#motor").hide();

		msgid=102;
		if (charttype=="optaccel") {
			$("#accel").show();
			chart_accel();
		} else if (charttype=="optgyro") {
			$("#gyro").show();
			chart_gyro();
		} else if (charttype=="optmag") {
			$("#mag").show();
			chart_mag();
		} else if (charttype=="optmotor") {
			$("#motor").show();
			chart_motor();
			msgid=104;
		}
	}
		
	if (msgid==0) return;

	var msg;

	msg = mw.serialize({
		"id": msgid
	});
	
	ws.send(msg);

	if (chart_g.render) chart_g.render();
	if (chart_a.render) chart_a.render();
	if (chart_m.render) chart_m.render();
	if (chart_motor.render) chart_motor.render();
}

function chart_add(data) {
	dps_a[0].push({'x':xVal, 'y':data.accx});
	dps_a[1].push({'x':xVal, 'y':data.accy});
	dps_a[2].push({'x':xVal, 'y':data.accz});

	dps_g[0].push({'x':xVal, 'y':data.gyrx});
	dps_g[1].push({'x':xVal, 'y':data.gyry});
	dps_g[2].push({'x':xVal, 'y':data.gyrz});

	dps_m[0].push({'x':xVal, 'y':data.magx});
	dps_m[1].push({'x':xVal, 'y':data.magy});
	dps_m[2].push({'x':xVal, 'y':data.magz});	

	if (dps_a[0].length > dataLength) {
		dps_a[0].shift();
		dps_a[1].shift();
		dps_a[2].shift();

		dps_g[0].shift();
		dps_g[1].shift();
		dps_g[2].shift();

		dps_m[0].shift();
		dps_m[1].shift();
		dps_m[2].shift();		
	}

	xVal++;
}

function chart_motor_add(data) {
	dps_motor[0].push({'x':xVal, 'y':data.motor1});
	dps_motor[1].push({'x':xVal, 'y':data.motor2});
	dps_motor[2].push({'x':xVal, 'y':data.motor3});
	dps_motor[3].push({'x':xVal, 'y':data.motor4});

	if (dps_motor[0].length > dataLength) {
		dps_motor[0].shift();
		dps_motor[1].shift();
		dps_motor[2].shift();
		dps_motor[3].shift();	
	}

	xVal++;
}

function msg_raw_imu(data) {
	counter++;

	$("#accx").text(data.accx); 
	$("#accy").text(data.accy); 
	$("#accz").text(data.accz); 
	$("#gyrx").text(data.gyrx);
	$("#gyry").text(data.gyry);
	$("#gyrz").text(data.gyrz); 
	$("#magx").text(data.magx);
	$("#magy").text(data.magy);
	$("#magz").text(data.magz);

	chart_add(data);
}

function msg_motor(data) {
	counter++;

	$("#motor1").text(data.motor1); 
	$("#motor2").text(data.motor2); 
	$("#motor3").text(data.motor3); 
	$("#motor4").text(data.motor4); 
	$("#motor5").text(data.motor5); 
	$("#motor6").text(data.motor6); 
	$("#motor7").text(data.motor7); 
	$("#motor8").text(data.motor8); 

	chart_motor_add(data);
}

function chart_accel() {
	chart_a = new CanvasJS.Chart("chart_a", {
	 animationEnabled: false,
	exportEnabled: true,
      title:{
        text: "Accel"              
      },
      legend: default_legend, 
      data: [//array of dataSeries              
        {
         	type: "line",
	 		markerType: "line",
	 		dataPoints: dps_a[0],
			showInLegend: true,
			name: 'AccX'
       },
        {
         	type: "line",
	 		markerType: "line",
	 		dataPoints: dps_a[1],
			showInLegend: true,
			name: 'AccY'
       },
        {
         	type: "line",
	 		markerType: "line",
	 		dataPoints: dps_a[2],
			showInLegend: true,
			name: 'AccZ'
       }     
       ]
     });
}

function chart_gyro() {
	chart_g = new CanvasJS.Chart("chart_g", {
	 animationEnabled: false,
	exportEnabled: true,
      title:{
        text: "Gyro"              
      },
      legend: default_legend, 
      data: [//array of dataSeries              
        {
         	type: "line",
	 		markerType: "line",
	 		dataPoints: dps_g[0],
			showInLegend: true,
			name: 'GyrX'
       },
        {
         	type: "line",
	 		markerType: "line",
	 		dataPoints: dps_g[1],
			showInLegend: true,
			name: 'GyrY'
       },
        {
         	type: "line",
	 		markerType: "line",
	 		dataPoints: dps_g[2],
			showInLegend: true,
			name: 'GyrZ'
       }     
       ]
     });
}

function chart_mag() {
	chart_m = new CanvasJS.Chart("chart_m", {
	 animationEnabled: false,
	exportEnabled: true,
      title:{
        text: "Mag"              
      },
      legend: default_legend, 
      data: [//array of dataSeries              
        {
         	type: "line",
	 		markerType: "line",
	 		dataPoints: dps_m[0],
			showInLegend: true,
			name: 'MagX'
       },
        {
         	type: "line",
	 		markerType: "line",
	 		dataPoints: dps_m[1],
			showInLegend: true,
			name: 'MagY'
       },
        {
         	type: "line",
	 		markerType: "line",
	 		dataPoints: dps_m[2],
			showInLegend: true,
			name: 'MagZ'
       }     
       ]
     });
}

function chart_motor() {
	chart_m = new CanvasJS.Chart("chart_motor", {
	 animationEnabled: false,
	exportEnabled: true,
      title:{
        text: "Motors"              
      },
      legend: default_legend, 
       axisY:{
   			minimum: 1100,
 		},
      data: [//array of dataSeries              
        {
         	type: "line",
	 		markerType: "line",
	 		dataPoints: dps_motor[0],
			showInLegend: true,
			name: 'BackRight'
       },
        {
         	type: "line",
	 		markerType: "line",
	 		dataPoints: dps_motor[1],
			showInLegend: true,
			name: 'FrontRight'
       },
        {
         	type: "line",
	 		markerType: "line",
	 		dataPoints: dps_motor[2],
			showInLegend: true,
			name: 'BackLeft'
       },
        {
         	type: "line",
	 		markerType: "line",
	 		dataPoints: dps_motor[3],
			showInLegend: true,
			name: 'FrontLeft'
       }  
       ]
     });
}

default_legend = {
	cursor: "pointer",
	itemclick: function (e) {
		if (typeof (e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
			e.dataSeries.visible = false;
		} else {
			e.dataSeries.visible = true;
		}
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
				case 101: init_with_status(data); break;
				case 102: msg_raw_imu(data); break;
				case 104: msg_motor(data); break;
			}
		} else {
			//console.log(data);
		}

	} while (data.err == undefined); 
	
	
	$("#update_time").text(get_time()); 

}

</script>

