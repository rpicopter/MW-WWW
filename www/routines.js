function get_time() {
          var d = new Date(),
      h = (d.getHours()<10?'0':'') + d.getHours(),
      m = (d.getMinutes()<10?'0':'') + d.getMinutes(),
      s = (d.getSeconds()<10?'0':'') + d.getSeconds();
      return h+":"+m+":"+s;
}

function mw_send(id,ab) { //id and data (arraybuffer)
	var data = mw_serialize(id,new Uint8Array(ab));
	console.log("Sending: ",data);
        ws.send(data);
}

/* 
	Input:
		id: message_id to be sent
		udata: uint8buffer of data  
	Output: serialized msp message

	Example input:
	id: 100
	udata: [100,0,1]

	Example output:
	[$,M,<,2,100,0,1,39]  //TODO: verify crc (39) is actualy correct
*/

function mw_serialize(id,udata) {
	var data = new ArrayBuffer(6+udata.length);
	var dv = new DataView(data);
	dv.setUint8(0,36); //$	
	dv.setUint8(1,77); //M	
	dv.setUint8(2,60); //<
	dv.setUint8(3,udata.length);
	dv.setUint8(4,id);
	for (i=0;i<udata.length;i++)
		dv.setUint8(5+i,udata[i]);

	var crc = udata.length;
	crc ^= id;
	for (i=0;i<udata.length;i++)
		crc ^= udata[i];
	//TODO: potential problem with endiness?
	dv.setUint8(i,crc);

	return data;
}

function mw_recv() {
        var ret = {data:[]};
        var len = ws.rQlen();
	return {err: "Received only: "+len+" bytes!!"};
	
/*
        for (var i=0;i<len;i+=4) {
                var ab = new ArrayBuffer(4);
                var ia = new Uint8Array(ab);
                ia[0] = ws.rQshift8();
                ia[1] = ws.rQshift8();
                ia[2] = ws.rQshift8();
                ia[3] = ws.rQshift8();
                var dv = new DataView(ab);
                var d = dv.getUint8(0);
                var t = dv.getUint8(1);
                var v = dv.getInt16(2);
                if (d==1) {
                        ws.close();
                        return {msg:"Disconnect request"};
                }

                ret.data.push({'t':t,'v':v});
        }
        return ret;
*/
}

