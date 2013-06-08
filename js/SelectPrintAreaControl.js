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