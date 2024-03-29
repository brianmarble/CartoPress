;(function(){
	var scriptEls = document.getElementsByTagName('script');
	var thisScript = scriptEls[scriptEls.length -1].src;
	var thisDir = thisScript.substr(0,thisScript.lastIndexOf('/'));
	jsDir = "/js/";
	jsFiles = [
		"CartoPress.js",
		"SVGRenderer.js",
		"SVGConverter.js",
		"SelectPrintAreaControl.js",
		"OSM.js",
		"Google.js"
	];
	var scriptTags = '';
	for(var i = 0; i < jsFiles.length; i++){
		var filename = thisDir+jsDir+jsFiles[i];
		if(filename != thisScript){
			scriptTags += '<script src="'+thisDir+jsDir+jsFiles[i]+'"></script>';
		}
	}
	document.write(scriptTags);
}());
