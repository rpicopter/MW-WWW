<div class="starter-template">
<p>Current time: <span id="current_time"/></p>
<p>Last updated: <span id="update_time"/></p>
<hr/>
<div id="items">
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

    value = [];
    isLoaded = false;
}

function start() {
	//console.log("Connected to mw proxy");
	var msg;

	msg = mw.filters([113,116]); //filters need to be sent as the first message on a new connection to mw proxy
	ws.send( msg );

	//request box names
	msg = mw.serialize({
		"id": 116
	});
	ws.send(msg);

	setInterval(update,1000); //keep sending the request
}

function update() {
	var msg;

	$("#current_time").text(get_time()); 

	msg = mw.serialize({
		"id": 113
	});
	ws.send(msg);
}

function msg_box(data) {
	for (var i=0;i<mw.BOXNAME.length;i++) {
		value[i] = data.value[i];
		$("#"+mw.BOXNAME[i]+"v").text(data.value[i]);
		
	}
}

function msg_boxnames(data) {
	if (isLoaded) return;
	isLoaded=true;
	var node;
	for (var i=0;i<data.boxname.length;i++) {
		node = "<div><p id=\""+data.boxname[i]+"\">"+data.boxname[i]+": <span id=\""+data.boxname[i]+"v\"/>"
		node += "<button id=\""+data.boxname[i]+"b\" type=\"button\" class=\"btn btn-primary\">Toggle</button></p></div>";
		$("#items").append(node);
		$("#"+data.boxname[i]+"b").val(i);
		$("#"+data.boxname[i]+"b").value = i;
		$("#"+data.boxname[i]+"b").click( function() { toggle(arguments); } );
	}
}

function toggle(b) {
	var msg;
	var i = $(b[0].target)[0].value;
	value[i] = 1-parseInt(value[i]);
	var data = {
		"id": 203,
		"value": []
	};
	for (var j=0;j<value.length;j++)
		data.value[j] = value[j];

	ws.send(mw.serialize(data));
}

function websock_recv() { //we have received a message
	var data;
	do { //receive messages in a loop to ensure we got all of them
		data = mw_recv();
		if (data.err == undefined) { //if err is set it means there was a genuine error or we haven't received enough data to proceed yet
			//console.log("Received: ",data);
			///populate screen with data
			switch (data.id) {
				case 113: msg_box(data); break;
				case 116: msg_boxnames(data); break;
			}
		} else {
			//console.log(data);
		}

	} while (data.err == undefined); 
	
	
	$("#update_time").text(get_time()); 

}

</script>

