
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