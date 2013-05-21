'use strict';

// bind shim
if (!Function.prototype.bind) {
  Function.prototype.bind = function (oThis) {
    if (typeof this !== "function") {
      // closest thing possible to the ECMAScript 5 internal IsCallable function
      throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
    }
 
    var aArgs = Array.prototype.slice.call(arguments, 1), 
        fToBind = this, 
        fNOP = function () {},
        fBound = function () {
          return fToBind.apply(this instanceof fNOP && oThis
              ? this
              : oThis,
            aArgs.concat(Array.prototype.slice.call(arguments)));
        };
 
    fNOP.prototype = this.prototype;
    fBound.prototype = new fNOP();
 
    return fBound;
  };
}

// forEach shim
if ( !Array.prototype.forEach ) {
  Array.prototype.forEach = function(fn, scope) {
    for(var i = 0, len = this.length; i < len; ++i) {
      fn.call(scope, this[i], i, this);
    }
  }
}

var CartoPress = function(map,url){
	this.url = url || CartoPress.serverUrl;
	this.request = OpenLayers.Request.XMLHttpRequest;
	this.json = new OpenLayers.Format.JSON();
	if(!this.request){
		throw "CartoPress Error: Unable to create XMLHttpRequest object!"
	}
	this.selectPrintAreaControl = new CartoPress.SelectPrintAreaControl();
	map.addControl(this.selectPrintAreaControl);
	this.sendAjaxForPageLayouts();
}

CartoPress.serverUrl = (function(){
	var scriptEl = document.body ? document.body.lastChild : document.head.lastChild;
	return scriptEl.src.replace(/js/,'php');
}());

CartoPress.util = {
	toRatio: function(bounds,ratio){
		var size = bounds.getSize(),
			heightDifference = size.w / ratio - size.h,
			widthDifference = ratio / size.h - size.w,
			adjustHeight = heightDifference > 0,
			extendSize = (adjustHeight ? heightDifference : widthDifference)/2,
			extendSides = adjustHeight ? [3,1] : [2,0],
			bArray = bounds.toArray();
		bArray[extendSides[0]] += extendSize;
		bArray[extendSides[1]] -= extendSize;
		var newBounds = OpenLayers.Bounds.fromArray(bArray);
		if(Number.isNaN(extendSize)){
			console.log('ratio', ratio);
			console.log('heightDifference', heightDifference);
			console.log('widthDifference', widthDifference);
			console.log('adjustHeight', adjustHeight);
			console.log('extendSize', extendSize);
			console.log('extendSides', extendSides);
			
			console.log(bounds,extendSize,adjustHeight,newBounds);
			throw 'ouch!';
		}
		return newBounds;
	},
}

CartoPress.prototype = {
	
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
		var name = "CartoPress_"+(new Date().getTime());
		var map = this.selectPrintAreaControl.map;
		cp.createPdf(map,bounds,this.currentLayout,name,callback);
	}
}

CartoPress.SelectPrintAreaControl = OpenLayers.Class(OpenLayers.Control.ModifyFeature, {

	printAreaRatio: 1,

	initialize: function() {
		OpenLayers.Control.ModifyFeature.prototype.initialize.call(this, 
			new OpenLayers.Layer.Vector('selectPrintArea'), 
			{
				mode: OpenLayers.Control.ModifyFeature.RESIZE | OpenLayers.Control.ModifyFeature.DRAG
			}
		);
		this.events.on({
			"activate": this._activate,
			"deactivate": this._deactivate,
			scope: this
		});
		
	},
	
	_activate: function(){
		this.layer.removeAllFeatures();
		this.map.addLayer(this.layer);
		
		var mapArea = this.map.getExtent();
		var printArea = CartoPress.util.toRatio(mapArea,this.printAreaRatio);
		
		var widthDiff = mapArea.getWidth() / printArea.getWidth();
		var heightDiff = mapArea.getHeight() / printArea.getHeight();
		
		printArea = printArea.scale(Math.min(widthDiff,heightDiff) * .75);
		var pageFeature = new OpenLayers.Feature.Vector(printArea.toGeometry());
		this.layer.addFeatures(pageFeature);
		this.selectControl.select(pageFeature);
	},
	
	_deactivate: function(){
		this.map.removeLayer(this.layer);
	},
	
	setRatio: function(ratio){
		this.printAreaRatio = ratio;
		if(this.active){
			this.deactivate();
			this.activate();
		}
	},
	
	getBounds: function(){
		return this.layer.features[0].geometry.getBounds();
	},
	
	CLASS_NAME: "CartoPress.SelectPrintArea"
});

CartoPress.GeoJson = OpenLayers.Class(OpenLayers.Format.GeoJSON,{

	read: function(json,type,filter){
		var features = OpenLayers.Format.GeoJSON.prototype.read.call(this, json,type,filter);
		for(var i = 0; i < features.length; i++){
			features[i].style = features[i].attributes.style;
			delete features[i].attributes.style;
		}
		return features;
	},

	write: function(features,pretty){
		for (var i = 0; i < features.length ; i++) {
			features[i].attributes.style = features[i].style;
		}
		var json = OpenLayers.Format.GeoJSON.prototype.write.call(this,features,pretty);
		for (i = 0; i < features.length ; i++) {
			delete features[i].attributes.style;
		}
		return json;
	}
});

CartoPress.SVGRenderer = OpenLayers.Class(OpenLayers.Renderer.SVG,{
	supported: function(){return true;}
});

CartoPress.SVGConverter = OpenLayers.Class({
	initialize: function(){},
	convert: function(origLayer,bounds){
		var layer = origLayer.clone();
		var map = new OpenLayers.Map({
			maxExtent: bounds,
			center: origLayer.map.getCenter(),
			resolution: origLayer.map.getResolution(),
			allOverlays: true
		});
		map.addLayer(layer);
		var container = document.createElement('div');
		var renderer = new CartoPress.SVGRenderer(container);
		renderer.map = {
			getResolution: function(){
				return map.getResolution();
			},
			calculateBounds: function(){
				return map.calculateBounds();
			}
		};
		var s = bounds.getSize(),
			r = origLayer.map.getResolution(),
			w = s.w /r,
			h = s.h /r;
		
		renderer.setSize(new OpenLayers.Size(w,h));
		renderer.setExtent(bounds); // could be used to set extent of printed page?
		layer.features.forEach(function(feature){
			renderer.drawFeature(feature);
		});
		var svg = container.firstChild;
		svg.setAttribute("xmlns","http://www.w3.org/2000/svg");
		return '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'+container.innerHTML;
	}
});

