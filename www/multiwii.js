/* This implements MultiWii websocket proxy protocol */

function MultiWii() {
	MAX_MSG_LEN = 32;
}


/* LIST OF ALL SERIALIZERS & PARSERS */
/* http://www.multiwii.com/wiki/index.php?title=Multiwii_Serial_Protocol */

/* Note that this is not the actual MSP protocol. 
/* The messages from JS are sent to mw server through mw proxy. Proxy and server together will convert them into actual MSP compliant format */

MultiWii.prototype.serialize_id100 = function(dv) {
	//the data should always start from 2nd byte
	return 0; //length of data
};

MultiWii.prototype.parse_id100 = function(dv) { 
	return {
		//the actual data starts from 2nd byte
		'version': dv.getUint8(2),
		'multitype': dv.getUint8(3),
		'msp_version': dv.getUint8(4),
		'capability': dv.getUint32(5)
	}
};

MultiWii.prototype.serialize_id101 = function(dv) {
	return 0;
};

MultiWii.prototype.parse_id101 = function(dv) { 
	return {
		'cycleTime': dv.getUint16(2),
		'i2c_errors_count': dv.getUint16(4),
		'sensor': dv.getUint16(6),
		'flag': dv.getUint32(8),
		'global_conf.currentSet': dv.getUint8(12)
	}
};



MultiWii.prototype.filters = function(data) {
	var arr = [];
	arr[0] = data.length;
	for (var i=0;i<data.length;i++)
		arr[i+1] = data[i];

	return arr;
};

MultiWii.prototype.serialize = function(data) {
	var id = data["id"];
	var ret = new Uint8Array(MAX_MSG_LEN);
	var dv = new DataView(ret.buffer);

	var _f = "serialize_id"+id;
	if (this[_f] == undefined) {
		console.log("Serializer for id: "+id+" not implemented!");
		return [];
	}
	var len = this[_f](dv);

	var arr = [len,id];
	for (var i=0;i<len;i++)
		arr[i+1] = ret[i]; 
	return arr;
};


MultiWii.prototype.parse = function(data) {/*array*/
	//console.log("Parsing data",data);
	var id, data_length;
	var arr = new Uint8Array(data);
	var dv = new DataView(arr.buffer);

	if (data.length<2) {
		return {"err": "Not enough data to parse! "+data.length};
	}

	data_length = dv.getUint8(0);
	id = dv.getUint8(1);

	if (data.length<2+data_length) {
		return {"err": "Not enough data to parse! "+data.length};
	}

	var _f = "parse_id"+id;
	if (this[_f] == undefined) {
		console.log("Parser for id: "+id+" not implemented!");
		return [];
	}

	var ret = this[_f](dv);
	ret.id = id;
	return ret;
}

MultiWii();


