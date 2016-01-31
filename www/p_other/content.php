<div class="starter-template">
<p>Last updated: <span id="update_time"/></p>
<hr/>
<p class="lead">
	CHARTS
</p>
		<p class="llabel">Update rate: <span class="value" id="rate"/></p>
		<div style="height: 400px;" id="chart_a">
			ACCEL CHART
		</div>
		<div style="height: 400px;" id="chart_g">
			GYRO CHART
		</div>
		<div style="height: 400px;" id="chart_m">
			MAG CHART
		</div>		
<p class="lead">
	RAW IMU
</p>		
		<p class="llabel">accx: <span class="value" id="accx"/></p>
		<p class="llabel">accy: <span class="value" id="accy"/></p>
		<p class="llabel">accz: <span class="value" id="accz"/></p>
		<p class="llabel">gyrx: <span class="value" id="gyrx"/></p>
		<p class="llabel">gyry: <span class="value" id="gyry"/></p>
		<p class="llabel">gyrz: <span class="value" id="gyrz"/></p>
		<p class="llabel">magx: <span class="value" id="magx"/></p>
		<p class="llabel">magy: <span class="value" id="magy"/></p>
		<p class="llabel">magz: <span class="value" id="magz"/></p>
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
}


function start() {
	//console.log("Connected to mw proxy");
	var msg;

	msg = mw.filters([102]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );
	

	dataLength = 500;
	xVal = 0;
	dps_a = [[],[],[]]; 
	dps_g = [[],[],[]]; 
	dps_m = [[],[],[]];  
	chart_accel();
	chart_gyro();
	chart_mag();

	setInterval(update,50); //keep sending the requests every 200ms
	setInterval(update_rate,1000); //keep sending the requests every 200ms

}

function update_rate() {
	$("#rate").text(counter);
	counter = 0;
}

function update() {
	var msg;

	msg = mw.serialize({
		"id": 102
	});
	
	ws.send(msg);
	chart_a.render();
	chart_g.render();
	chart_m.render();
}

function chart_add(data) {
	if (xVal==0)
		accz_0 = 0;
	xVal++;

	dps_a[0].push({'x':xVal, 'y':data.accx});
	dps_a[1].push({'x':xVal, 'y':data.accy});
	dps_a[2].push({'x':xVal, 'y':data.accz-accz_0});

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
				case 102: msg_raw_imu(data); break;
			}
		} else {
			//console.log(data);
		}

	} while (data.err == undefined); 
	
	
	$("#update_time").text(get_time()); 

}

</script>

