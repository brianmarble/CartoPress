

CartoPress.SVGRenderer = OpenLayers.Class(OpenLayers.Renderer.SVG, {

    supported: function() {
        return true;
    },

    setExtent: function(extent, resolutionChanged) {
        var coordSysUnchanged = OpenLayers.Renderer.Elements.prototype.setExtent.apply(this, arguments);

        var resolution = this.getResolution(),
            left = -extent.left / resolution,
            top = extent.top / resolution;

        if (resolutionChanged) {
            this.left = left;
            this.top = top;

            var extentString = "0 0 " + this.size.w + " " + this.size.h;

            this.rendererRoot.setAttribute( "viewBox", extentString);
            this.translate(this.xOffset, 0);
            return true;
        } else {
            var inRange = this.translate(left - this.left + this.xOffset, top - this.top);
            if (!inRange) {

                this.setExtent(extent, true);
            }
            return coordSysUnchanged && inRange;
        }
    },

    translate: function(x, y) {
        if (!this.inValidRange(x, y, true)) {
            return false;
        } else {
            var transformString = "";
            if (x || y) {
                transformString = "translate(" + x + "," + y + ")";
            }
            this.root.setAttribute( "transform", transformString);
            this.translationParameters = {x: x, y: y};
            return true;
        }
    },

    setSize: function(size) {
        OpenLayers.Renderer.prototype.setSize.apply(this, arguments);

        this.rendererRoot.setAttribute( "width", this.size.w);
        this.rendererRoot.setAttribute( "height", this.size.h);
    },

    setStyle: function(node, style, options) {
        style = style  || node._style;
        options = options || node._options;
        var r = parseFloat(node.getAttribute( "r"));
        var widthFactor = 1;
        var pos;
        if (node._geometryClass == "OpenLayers.Geometry.Point" && r) {
            node.style.visibility = "";
            if (style.graphic === false) {
                node.style.visibility = "hidden";
            } else if (style.externalGraphic) {
                pos = this.getPosition(node);

                if (style.graphicTitle) {
                    node.setAttribute( "title", style.graphicTitle);

                    var titleNode = node.getElementsByTagName("title");
                    if (titleNode.length > 0) {
                        titleNode[0].firstChild.textContent = style.graphicTitle;
                    } else {
                        var label = this.nodeFactory(null, "title");
                        label.textContent = style.graphicTitle;
                        node.appendChild(label);
                    }
                }
                if (style.graphicWidth && style.graphicHeight) {
                  node.setAttribute( "preserveAspectRatio", "none");
                }
                var width = style.graphicWidth || style.graphicHeight;
                var height = style.graphicHeight || style.graphicWidth;
                width = width ? width : style.pointRadius*2;
                height = height ? height : style.pointRadius*2;
                var xOffset = (style.graphicXOffset != undefined) ?
                    style.graphicXOffset : -(0.5 * width);
                var yOffset = (style.graphicYOffset != undefined) ?
                    style.graphicYOffset : -(0.5 * height);

                var opacity = style.graphicOpacity || style.fillOpacity;

                node.setAttribute( "x", (pos.x + xOffset).toFixed());
                node.setAttribute( "y", (pos.y + yOffset).toFixed());
                node.setAttribute( "width", width);
                node.setAttribute( "height", height);
                //node.setAttributeNS(this.xlinkns, "href", style.externalGraphic);
                node.setAttribute( "style", "opacity: "+opacity);
                node.onclick = OpenLayers.Renderer.SVG.preventDefault;
            } else if (this.isComplexSymbol(style.graphicName)) {

                var offset = style.pointRadius * 3;
                var size = offset * 2;
                var src = this.importSymbol(style.graphicName);
                pos = this.getPosition(node);
                widthFactor = this.symbolMetrics[src.id][0] * 3 / size;

                var parent = node.parentNode;
                var nextSibling = node.nextSibling;
                if(parent) {
                    parent.removeChild(node);
                }

                node.firstChild && node.removeChild(node.firstChild);
                node.appendChild(src.firstChild.cloneNode(true));
                node.setAttribute( "viewBox", src.getAttribute( "viewBox"));

                node.setAttribute( "width", size);
                node.setAttribute( "height", size);
                node.setAttribute( "x", pos.x - offset);
                node.setAttribute( "y", pos.y - offset);

                if(nextSibling) {
                    parent.insertBefore(node, nextSibling);
                } else if(parent) {
                    parent.appendChild(node);
                }
            } else {
                node.setAttribute( "r", style.pointRadius);
            }

            var rotation = style.rotation;

            if ((rotation !== undefined || node._rotation !== undefined) && pos) {
                node._rotation = rotation;
                rotation |= 0;
                if (node.nodeName !== "svg") {
                    node.setAttribute( "transform",
                        "rotate(" + rotation + " " + pos.x + " " +
                        pos.y + ")");
                } else {
                    var metrics = this.symbolMetrics[src.id];
                    node.firstChild.setAttribute( "transform", "rotate("
                        + rotation + " "
                        + metrics[1] + " "
                        + metrics[2] + ")");
                }
            }
        }

        if (options.isFilled) {
            node.setAttribute( "fill", style.fillColor);
            node.setAttribute( "fill-opacity", style.fillOpacity);
        } else {
            node.setAttribute( "fill", "none");
        }

        if (options.isStroked) {
            node.setAttribute( "stroke", style.strokeColor);
            node.setAttribute( "stroke-opacity", style.strokeOpacity);
            node.setAttribute( "stroke-width", style.strokeWidth * widthFactor);
            node.setAttribute( "stroke-linecap", style.strokeLinecap || "round");

            node.setAttribute( "stroke-linejoin", "round");
            style.strokeDashstyle && node.setAttribute(
                "stroke-dasharray", this.dashStyle(style, widthFactor));
        } else {
            node.setAttribute( "stroke", "none");
        }

        if (style.pointerEvents) {
            node.setAttribute( "pointer-events", style.pointerEvents);
        }

        if (style.cursor != null) {
            node.setAttribute( "cursor", style.cursor);
        }

        return node;
    },

    createNode: function(type, id) {
        var node = document.createElement(type);
        if (id) {
            node.setAttribute( "id", id);
        }
        return node;
    },

    drawCircle: function(node, geometry, radius) {
        var resolution = this.getResolution();
        var x = ((geometry.x - this.featureDx) / resolution + this.left);
        var y = (this.top - geometry.y / resolution);

        if (this.inValidRange(x, y)) {
            node.setAttribute( "cx", x);
            node.setAttribute( "cy", y);
            node.setAttribute( "r", radius);
            return node;
        } else {
            return false;
        }

    },

    drawLineString: function(node, geometry) {
        var componentsResult = this.getComponentsString(geometry.components);
        if (componentsResult.path) {
            node.setAttribute( "points", componentsResult.path);
            return (componentsResult.complete ? node : null);
        } else {
            return false;
        }
    },

    drawLinearRing: function(node, geometry) {
        var componentsResult = this.getComponentsString(geometry.components);
        if (componentsResult.path) {
            node.setAttribute( "points", componentsResult.path);
            return (componentsResult.complete ? node : null);
        } else {
            return false;
        }
    },

    drawPolygon: function(node, geometry) {
        var d = "";
        var draw = true;
        var complete = true;
        var linearRingResult, path;
        for (var j=0, len=geometry.components.length; j<len; j++) {
            d += " M";
            linearRingResult = this.getComponentsString(
                geometry.components[j].components, " ");
            path = linearRingResult.path;
            if (path) {
                d += " " + path;
                complete = linearRingResult.complete && complete;
            } else {
                draw = false;
            }
        }
        d += " z";
        if (draw) {
            node.setAttribute( "d", d);
            node.setAttribute( "fill-rule", "evenodd");
            return complete ? node : null;
        } else {
            return false;
        }
    },

    drawRectangle: function(node, geometry) {
        var resolution = this.getResolution();
        var x = ((geometry.x - this.featureDx) / resolution + this.left);
        var y = (this.top - geometry.y / resolution);

        if (this.inValidRange(x, y)) {
            node.setAttribute( "x", x);
            node.setAttribute( "y", y);
            node.setAttribute( "width", geometry.width / resolution);
            node.setAttribute( "height", geometry.height / resolution);
            return node;
        } else {
            return false;
        }
    },

    drawText: function(featureId, style, location) {
        var drawOutline = (!!style.labelOutlineWidth);

        if (drawOutline) {
            var outlineStyle = OpenLayers.Util.extend({}, style);
            outlineStyle.fontColor = outlineStyle.labelOutlineColor;
            outlineStyle.fontStrokeColor = outlineStyle.labelOutlineColor;
            outlineStyle.fontStrokeWidth = style.labelOutlineWidth;
            delete outlineStyle.labelOutlineWidth;
            this.drawText(featureId, outlineStyle, location);
        }

        var resolution = this.getResolution();

        var x = ((location.x - this.featureDx) / resolution + this.left);
        var y = (location.y / resolution - this.top);

        var suffix = (drawOutline)?this.LABEL_OUTLINE_SUFFIX:this.LABEL_ID_SUFFIX;
        var label = this.nodeFactory(featureId + suffix, "text");

        label.setAttribute( "x", x);
        label.setAttribute( "y", -y);

        if (style.fontColor) {
            label.setAttribute( "fill", style.fontColor);
        }
        if (style.fontStrokeColor) {
            label.setAttribute( "stroke", style.fontStrokeColor);
        }
        if (style.fontStrokeWidth) {
            label.setAttribute( "stroke-width", style.fontStrokeWidth);
        }
        if (style.fontOpacity) {
            label.setAttribute( "opacity", style.fontOpacity);
        }
        if (style.fontFamily) {
            label.setAttribute( "font-family", style.fontFamily);
        }
        if (style.fontSize) {
            label.setAttribute( "font-size", style.fontSize);
        }
        if (style.fontWeight) {
            label.setAttribute( "font-weight", style.fontWeight);
        }
        if (style.fontStyle) {
            label.setAttribute( "font-style", style.fontStyle);
        }
        if (style.labelSelect === true) {
            label.setAttribute( "pointer-events", "visible");
            label._featureId = featureId;
        } else {
            label.setAttribute( "pointer-events", "none");
        }
        var align = style.labelAlign || OpenLayers.Renderer.defaultSymbolizer.labelAlign;
        label.setAttribute( "text-anchor",
            OpenLayers.Renderer.SVG.LABEL_ALIGN[align[0]] || "middle");

        if (OpenLayers.IS_GECKO === true) {
            label.setAttribute( "dominant-baseline",
                OpenLayers.Renderer.SVG.LABEL_ALIGN[align[1]] || "central");
        }

        var labelRows = style.label.split('\n');
        var numRows = labelRows.length;
        while (label.childNodes.length > numRows) {
            label.removeChild(label.lastChild);
        }
        for (var i = 0; i < numRows; i++) {
            var tspan = this.nodeFactory(featureId + suffix + "_tspan_" + i, "tspan");
            if (style.labelSelect === true) {
                tspan._featureId = featureId;
                tspan._geometry = location;
                tspan._geometryClass = location.CLASS_NAME;
            }
            if (OpenLayers.IS_GECKO === false) {
                tspan.setAttribute( "baseline-shift",
                    OpenLayers.Renderer.SVG.LABEL_VSHIFT[align[1]] || "-35%");
            }
            tspan.setAttribute("x", x);
            if (i == 0) {
                var vfactor = OpenLayers.Renderer.SVG.LABEL_VFACTOR[align[1]];
                if (vfactor == null) {
                     vfactor = -.5;
                }
                tspan.setAttribute("dy", (vfactor*(numRows-1)) + "em");
            } else {
                tspan.setAttribute("dy", "1em");
            }
            tspan.textContent = (labelRows[i] === '') ? ' ' : labelRows[i];
            if (!tspan.parentNode) {
                label.appendChild(tspan);
            }
        }

        if (!label.parentNode) {
            this.textRoot.appendChild(label);
        }
    },

    getPosition: function(node) {
        return({
            x: parseFloat(node.getAttribute( "cx")),
            y: parseFloat(node.getAttribute( "cy"))
        });
    },

    importSymbol: function (graphicName)  {
        if (!this.defs) {

            this.defs = this.createDefs();
        }
        var id = this.container.id + "-" + graphicName;

        var existing = document.getElementById(id);
        if (existing != null) {
            return existing;
        }

        var symbol = OpenLayers.Renderer.symbol[graphicName];
        if (!symbol) {
            throw new Error(graphicName + ' is not a valid symbol name');
        }

        var symbolNode = this.nodeFactory(id, "symbol");
        var node = this.nodeFactory(null, "polygon");
        symbolNode.appendChild(node);
        var symbolExtent = new OpenLayers.Bounds(
                                    Number.MAX_VALUE, Number.MAX_VALUE, 0, 0);

        var points = [];
        var x,y;
        for (var i=0; i<symbol.length; i=i+2) {
            x = symbol[i];
            y = symbol[i+1];
            symbolExtent.left = Math.min(symbolExtent.left, x);
            symbolExtent.bottom = Math.min(symbolExtent.bottom, y);
            symbolExtent.right = Math.max(symbolExtent.right, x);
            symbolExtent.top = Math.max(symbolExtent.top, y);
            points.push(x, ",", y);
        }

        node.setAttribute( "points", points.join(" "));

        var width = symbolExtent.getWidth();
        var height = symbolExtent.getHeight();

        var viewBox = [symbolExtent.left - width,
                        symbolExtent.bottom - height, width * 3, height * 3];
        symbolNode.setAttribute( "viewBox", viewBox.join(" "));
        this.symbolMetrics[id] = [
            Math.max(width, height),
            symbolExtent.getCenterLonLat().lon,
            symbolExtent.getCenterLonLat().lat
        ];

        this.defs.appendChild(symbolNode);
        return symbolNode;
    }
    
});
