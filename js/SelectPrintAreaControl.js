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
		var printArea = this.toRatio(mapArea,this.printAreaRatio);
		
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
	
	toRatio: function(bounds,ratio){
		var size = bounds.getSize(),
			heightDifference = size.w / ratio - size.h,
			widthDifference =  size.h / (1/ratio) - size.w,
			adjustHeight = heightDifference > 0,
			extendSize = (adjustHeight ? heightDifference : widthDifference)/2,
			extendSides = adjustHeight ? [3,1] : [2,0],
			bArray = bounds.toArray();
			
		bArray[extendSides[0]] += extendSize;
		bArray[extendSides[1]] -= extendSize;
		
		var newBounds = OpenLayers.Bounds.fromArray(bArray);
		return newBounds;
	},
	
	CLASS_NAME: "CartoPress.SelectPrintArea"
});