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
		return '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'+container.innerHTML;
	}

});