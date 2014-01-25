'use strict';

CartoPress.Google = OpenLayers.Class({
	
	initialize: function(){},
	
	getSpec: function(layer,bounds){		
		var lonLatBounds = bounds.clone().transform(layer.map.getProjectionObject(), new OpenLayers.Projection("EPSG:4326"));
		return {
			name: layer.name,
			type: 'google',
			maptype: layer.type,
			lonLatBounds: {
				top: lonLatBounds.top,
				left: lonLatBounds.left,
				bottom: lonLatBounds.bottom,
				right: lonLatBounds.right
			},
			zoom: layer.map.getZoom(),
		};
	},
	
	CLASS_NAME: "CartoPress.Google"
});






