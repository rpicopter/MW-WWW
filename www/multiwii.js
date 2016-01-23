/* This implements MultiWii websocket proxy protocol */

function MultiWii() {
	MAX_MSG_LEN = 32;
	endiness = true; //used when reading and writing from DataView
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
	var caps = dv.getUint32(5,endiness);
	var ret = {
		//the actual data starts from 2nd byte
		'version': dv.getUint8(2,endiness),
		'multitype': dv.getUint8(3,endiness),
		'msp_version': dv.getUint8(4,endiness),
		//'capability': dv.getUint32(5,endiness),
		'capability': {
			'bind_capable': MultiWii.getBit(caps,0),
			'dynbal': MultiWii.getBit(caps,1),
			'flap': MultiWii.getBit(caps,2),
			'navcap': MultiWii.getBit(caps,3),
			'extaux': MultiWii.getBit(caps,4),
			'navi_version': 0
		}
	}

	return ret;
};

MultiWii.prototype.serialize_id101 = function(dv) {
	return 0;
};

MultiWii.prototype.parse_id101 = function(dv) { 
	var sensor = dv.getUint16(6,endiness);
	var ret = {
		'cycleTime': dv.getUint16(2,endiness),
		'i2c_errors_count': dv.getUint16(4,endiness),
		'sensor': {
			'baro': MultiWii.getBit(sensor,0),
			'iag': MultiWii.getBit(sensor,1),
			'gps': MultiWii.getBit(sensor,2),
			'sonar': MultiWii.getBit(sensor,3)
		},
		'flag': parseInt(dv.getUint32(8,endiness)).toString(2), //get binary format for the value
		'global_conf.currentSet': dv.getUint8(12,endiness)
	}
	return ret;
};

MultiWii.prototype.serialize_id108 = function(dv) {
	return 0;
};

MultiWii.prototype.parse_id108 = function(dv) { 
	var ret = {
		'angx': dv.getInt16(2,endiness),
		'angy': dv.getInt16(4,endiness),
		'heading': dv.getInt16(6,endiness)
	}
	return ret;
};

MultiWii.prototype.serialize_id205 = function(dv) {
	return 0;
};

MultiWii.prototype.serialize_id206 = function(dv) {
	return 0;
};

/* END OF PARSERS AND SERIALIZERS */

MultiWii.MultiType = ["?","TRI","QUADP","QUADX","BI","GIMBAL","Y6","HEX6","FLYING_WING","Y4","HEX6X","OCTOX8","OCTOFLATP","OCTOFLATX","AIRPLANE","HELI_120","HELI_90","VTAIL4","HEX6H","SINGLECOPTER","DUALCOPTER"];

MultiWii.getBit = function(val,bit) {
	//TODO: handle endiness correctly - check the endiness; use bitwise operations to get the correct bit
	//Would be nice to have a browser with different endiness for testing purposes
	var v = parseInt(val).toString(2);
	if (v[bit]==undefined) return 0;
	return v[bit];
}

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


