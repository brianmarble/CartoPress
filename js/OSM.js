'use strict';

CartoPress.OSM = OpenLayers.Class({
	
	initialize: function(){},
	
	getSpec: function(layer,bounds){
		var lonLatBounds = bounds.clone().transform(layer.map.getProjectionObject(), new OpenLayers.Projection("EPSG:4326"));
		return {
			name: layer.name,
			type: 'osm',
			url: layer.url,
			params: layer.params,
			lonLatBounds: {
				top: lonLatBounds.top,
				left: lonLatBounds.left,
				bottom: lonLatBounds.bottom,
				right: lonLatBounds.right
			}
		};
	},
	
	CLASS_NAME: "CartoPress.OSM"
});






