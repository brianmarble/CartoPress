'use strict';




var CartoPress = function(url){
	this.url = url || CartoPress.serverUrl;
	this.request = OpenLayers.Request.XMLHttpRequest;
	this.json = new OpenLayers.Format.JSON();
	if(!this.request){
		throw "CartoPress Error: Unable to create XMLHttpRequest object!"
	}
}

CartoPress.serverUrl = (function(){
	var scriptEl = document.body ? document.body.lastChild : document.head.lastChild;
	return scriptEl.src.replace(/js/,'php');
}());

CartoPress.prototype.getAvailableFormats = function(callback){
	var request =  new this.request();
	request.open('GET',this.url+'/formats');
	request._cp_callback = callback;
	request.setRequestHeader('Accept','application/json');
	request.onreadystatechange = this.handleResponse.bind(this);
	request.send();
};

CartoPress.prototype.handleResponse = function(event){
	var request = event.target;
	if(request.readyState == 4){
		try {
			var data = this.json.read(request.responseText);
			if(request._cp_callback instanceof Function){
				request._cp_callback(data);
			}
		} catch(e){
			if(e instanceof SyntaxError){
				console.log('CartoPress Error: Server Response isn\'t json!');
				console.log(request.responseText);
				return;
			} else {
				throw e;
			}
		}
	}
}

CartoPress.prototype.createPdf = function(map,bounds,format,id,callback){
	var bounds = this.toRatio(bounds,format.ratio);
	var data = {
		bounds: bounds,
		projection: map.getProjection(),
		pageSize: typeof format == "string" ? format : format.name,
		layers: []
	}
	var layers = map.getLayersBy('visibility',true);
	for(var i = 0; i < layers.length; i++){
		var layer = layers[i];
		var type = undefined;
		if(layer instanceof OpenLayers.Layer.WMS){
			data.layers.push(this.getWmsSpec(layer));
		} else if (layer instanceof OpenLayers.Layer.Vector){
			data.layers.push(this.getVectorSpec(layer));
		} else {
			console.log("Not printing layer: "+layer.name);
		}
	}
	
	var request =  new this.request();
	request.open('POST',this.url+'/pdfs/'+id);
	request._cp_callback = callback;
	request.setRequestHeader('Content-type','application/json');
	request.setRequestHeader('Accept','application/json');
	request.onreadystatechange = this.handleResponse.bind(this);
	request.send(this.json.write(data));
}

CartoPress.prototype.toRatio = function(bounds,ratio,shrink){
	var w = bounds.getWidth();
	var h = bounds.getHeight();
	var landscape = w > h;
	var longSide = landscape ? w : h;
	var shortSide = landscape ? h : w;
	var targetShortSide = longSide * ratio;
	var targetLongSide = shortSide * (1/ratio);
	var editLong = longSide < targetLongSide;
	if(shrink)editLong != editLong;
	var extension = editLong ? (targetLongSide - longSide) / 2 : (targetShortSide - shortSide) / 2
	var bArray = bounds.toArray();
	if(editLong && landscape){
		bArray[0] -= extension;
		bArray[2] += extension;
	} else {
		bArray[1] -= extension;
		bArray[3] += extension;
	}
	return OpenLayers.Bounds.fromArray(bArray);
}


CartoPress.prototype.getWmsSpec = function(layer){
	return {
		name: layer.name,
		type: 'wms',
		url: layer.url,
		params: layer.params
	}
}

CartoPress.prototype.getVectorSpec = function(layer){
	var gj = new OpenLayers.Format.GeoJSON();
	console.log(layer);
	return {
		name: layer.name,
		type: 'wms',
		features: gj.write(layer.features)
	}
}
