'use strict';

var CartoPress = OpenLayers.Class({
	
	activate: function(){
		this._selectPrintAreaControl.activate();
	},
	
	deactivate: function(){
		this._selectPrintAreaControl.deactivate();
	},
	
	getPageLayouts: function(callback){
		if(this._pageLayouts){
			var returnArray = [];
			for(var i = 0; i < this._pageLayouts.length; i++){
				returnArray.push(this._pageLayouts[i].name);
			}
			callback(returnArray);
		} else {
			this._pageLayoutsCallback = callback;
		}
	},
	
	setPageLayout: function(layout){
		for(var i = 0; i < this._pageLayouts.length; i++){
			if(this._pageLayouts[i].name === layout){
				this._currentLayout = layout;
				this._selectPrintAreaControl.setRatio(
					+this._pageLayouts[i].ratio
				);
				return;
			}
		}
		throw "Layout "+layout+" not found!";
	},
	
	print: function(callback){
		var bounds = this._selectPrintAreaControl.getBounds();
		this._selectPrintAreaControl.deactivate();
		var map = this._selectPrintAreaControl.map;
		this._createPdf(map,bounds,this._currentLayout,callback);
	},
	
	initialize: function(map,url,queryParams){
		this._baseUrl = url;
		this._queryParams = queryParams;
		this._selectPrintAreaControl = new CartoPress.SelectPrintAreaControl();
		map.addControl(this._selectPrintAreaControl);
		this._sendAjaxForPageLayouts();
	},
	
	_sendAjaxForPageLayouts: function(){
		this._ajax('GET','/formats',null,function(data){
			this._pageLayouts = data;
			if(this._pageLayoutsCallback instanceof Function){
				this.getPageLayouts(this._pageLayoutsCallback);
			}
		}.bind(this));
	},
	
	/*TODO remove*/
	_outputSyntaxError: function(errorText){
		var div = document.createElement('div');
		div.innerHTML = errorText;
		document.body.insertBefore(div,document.body.firstChild);
	},

	_createPdf: function(map,bounds,format,callback){
		var data = {
			bounds: bounds,
			projection: map.getProjection(),
			layout: typeof format == "string" ? format : format.name,
			layers: []
		}
		var layers = map.getLayersBy('visibility',true);
		for(var i = 0; i < layers.length; i++){
			var layer = layers[i];
			var type = undefined;
			if(layer instanceof OpenLayers.Layer.WMS){
				data.layers.push(this._getWmsSpec(layer));
			} else if (layer instanceof OpenLayers.Layer.Vector){
				data.layers.push(this._getVectorSpec(layer,bounds));
			} else {
				console.log("Not printing layer: "+layer.name);
			}
		}
		
		this._ajax('POST','/pdfs/create',data,function(data,status){
				if(callback instanceof Function){
					if(status == 201){
						callback(this._baseUrl+"/pdfs/"+data.url+this._getQueryString());
					} else {
						callback(false);
					}
				}
		}.bind(this));
	},

	_getWmsSpec: function(layer){
		return {
			name: layer.name,
			type: 'wms',
			url: layer.url,
			params: layer.params
		}
	},

	_getVectorSpec: function(layer,bounds){
		var svgc = new CartoPress.SVGConverter();
		var svg = svgc.convert(layer,bounds);
		return {
			name: layer.name,
			type: 'svg',
			svg: svg
		}
	},
	
	_getQueryString: function(){
		if(this._queryParams){
			var params = [];
			for(var p in this._queryParams)if(this._queryParams.hasOwnProperty(p)){
				params.push(encodeURIComponent(p)+'='+encodeURIComponent(this._queryParams[p]));
			}
			return '?'+params.join('&');
		} else {
			return '';
		}
	},
	
	_ajax: function(method,path,requestData,callback){
		var request = new OpenLayers.Request.XMLHttpRequest(),
			json = new OpenLayers.Format.JSON();
		request.open(method,this._baseUrl+path+this._getQueryString());
		request.setRequestHeader('Accept','application/json');
		if(requestData)request.setRequestHeader('Content-type','application/json');
		request.onreadystatechange = function(){
			if(request.readyState == 4){
				try {					
					var responseData = json.read(request.responseText);
				} catch(e){
					if(e instanceof SyntaxError){
						this._outputSyntaxError(request.responseText);
						return;
					} else {
						throw e;
					}
				}
				callback(responseData,request.status);
			}
		}.bind(this);
		request.send(json.write(requestData));
	},
	
	CLASS_NAME: "CartoPress"
});






