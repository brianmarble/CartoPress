'use strict';

var CartoPress = OpenLayers.Class({
	
	initialize: function(map,url){
		this.url = url;
		this.request = OpenLayers.Request.XMLHttpRequest;
		this.json = new OpenLayers.Format.JSON();
		if(!this.request){
			throw "CartoPress Error: Unable to create XMLHttpRequest object!"
		}
		this.selectPrintAreaControl = new CartoPress.SelectPrintAreaControl();
		map.addControl(this.selectPrintAreaControl);
		this.sendAjaxForPageLayouts();
	},
	
	sendAjaxForPageLayouts: function(){
		var request =  new this.request();
		request.open('GET',this.url+'/formats');
		request.setRequestHeader('Accept','application/json');
		request.onreadystatechange = this.recieveLayouts.bind(this);
		request.send();
	},
	
	getPageLayouts: function(callback){
		if(this.pageLayouts){
			var returnArray = [];
			for(var i = 0; i < this.pageLayouts.length; i++){
				returnArray.push(this.pageLayouts[i].name);
			}
			callback(returnArray);
		} else {
			this.pageLayoutsCallback = callback;
		}
	},

	recieveLayouts: function(event){
		var request = event.target;
		if(request.readyState == 4){
			try {
				this.pageLayouts = this.json.read(request.responseText);
			} catch(e){
				if(e instanceof SyntaxError){
					this.outputSyntaxError(request.responseText);
					return;
				} else {
					throw e;
				}
			}
			if(this.pageLayoutsCallback instanceof Function){
				this.getPageLayouts(this.pageLayoutsCallback);
			}
		}
	},
	
	outputSyntaxError: function(errorText){
		var div = document.createElement('div');
		div.innerHTML = errorText;
		document.body.insertBefore(div,document.body.firstChild);
	},

	createPdf: function(map,bounds,format,id,callback){
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
				data.layers.push(this.getWmsSpec(layer));
			} else if (layer instanceof OpenLayers.Layer.Vector){
				data.layers.push(this.getVectorSpec(layer,bounds));
			} else {
				console.log("Not printing layer: "+layer.name);
			}
		}
		
		var request =  new this.request();
		request.open('POST',this.url+'/pdfs/'+id+'.pdf');
		request._cp_callback = callback;
		request.setRequestHeader('Content-type','application/json');
		request.setRequestHeader('Accept','application/json');
		request.onreadystatechange = this.handleCreatePdfResponse.bind(this);
		request.send(this.json.write(data));
	},

	handleCreatePdfResponse: function(event){
		var request = event.target;
		if(request.readyState == 4){
			try {
				var data = this.json.read(request.responseText);
				if(request._cp_callback instanceof Function){
					if(request.status == 201){
						request._cp_callback(this.url+"/pdfs/"+data.url);
					} else {
						request._cp_callback(false);
					}
					
				}
			} catch(e){
				if(e instanceof SyntaxError){
					this.outputSyntaxError(request.responseText);
					if(request._cp_callback instanceof Function){
						request._cp_callback(false);
					}
					return;
				} else {
					throw e;
				}
			}
		}
	},

	getWmsSpec: function(layer){
		return {
			name: layer.name,
			type: 'wms',
			url: layer.url,
			params: layer.params
		}
	},

	getVectorSpec: function(layer,bounds){
		var svgc = new CartoPress.SVGConverter();
		var svg = svgc.convert(layer,bounds);
		return {
			name: layer.name,
			type: 'svg',
			svg: svg
		}
	},
	
	getFeaturesInBounds: function(features,bounds){
		var returnSet = [];
		var feature, fbounds;
		for(var i = 0; i < features.length; i++){
			feature = features[i];
			fbounds = feature.geometry.getBounds();
			if(bounds.intersectsBounds(fbounds)){
				returnSet.push(feature);
			}
		}
		return returnSet;
	},
	
	setPageLayout: function(layout){
		for(var i = 0; i < this.pageLayouts.length; i++){
			if(this.pageLayouts[i].name === layout){
				this.currentLayout = layout;
				this.selectPrintAreaControl.setRatio(
					+this.pageLayouts[i].ratio
				);
				return;
			}
		}
		throw "Layout "+layout+" not found!";
	},
	
	activate: function(){
		this.selectPrintAreaControl.activate();
	},
	
	deactivate: function(){
		this.selectPrintAreaControl.deactivate();
	},
	
	print: function(callback){
		var bounds = this.selectPrintAreaControl.getBounds();
		this.selectPrintAreaControl.deactivate();
		var name = this.uuid();//"CartoPress_"+(new Date().getTime());
		var map = this.selectPrintAreaControl.map;
		this.createPdf(map,bounds,this.currentLayout,name,callback);
	},
	
	uuid: function(){
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
			var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
			return v.toString(16);
		});
	},
	
	CLASS_NAME: "CartoPress"
});





