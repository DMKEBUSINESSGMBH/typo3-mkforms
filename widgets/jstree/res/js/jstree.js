Formidable.Classes.JsTree = Formidable.Classes.RdtBaseClass.extend({
	oTree: null,
	aHandlers: null,
	constructor: function(config) {
		this.base(config);
		this.aHandlers = {
			"onnodeclick": $A(),
			"onnodeopen": $A(),
			"onnodeclose": $A()
		};
	},
	init: function() {

		if(this.domNode()) {
			this.oTree = new AxentTree(
				this.domNode(), {
					"iconsFolder": this.config.abswebpath + "res/lib/img/",
					"nodeclick_handler": this.onNodeClick_eventHandler.bind(this),
					"nodeopen_handler": this.onNodeOpen_eventHandler.bind(this),
					"nodeclose_handler": this.onNodeClose_eventHandler.bind(this)
			});

			if(this.config.value != "" && this.config.value != 0) {
				this.oTree.setValue(this.config.value);
			}
		}
	},
	addHandler: function(sHandler, fFunction) {
		this.aHandlers[sHandler].push(fFunction);
	},
	onNodeClick_eventHandler: function(nodevalue) {
		this.aHandlers["onnodeclick"].each(function(fFunc, iKey) {
			fFunc(nodevalue);
		});
	},
	onNodeOpen_eventHandler: function(nodevalue) {
		this.aHandlers["onnodeopen"].each(function(fFunc, iKey) {
			fFunc(nodevalue);
		});
	},
	onNodeClose_eventHandler: function(nodevalue) {
		this.aHandlers["onnodeclose"].each(function(fFunc, iKey) {
			fFunc(nodevalue);
		});
	},
	getValue: function() {
		return this.oTree.getSelectedValue();
	},
	getSelectedLabel: function() {
		return this.oTree.getSelectedLabel();
	},
	getSelectedPath: function() {
		return this.oTree.getSelectedPath();
	},
	repaint: function(sHtml) {
		if(this.oTree) {
			this.oTree.unloadHandlers();
			this.oTree = null;
		}

		this.aHandlers.onnodeclick = $A();
		this.aHandlers.onnodeopen = $A();
		this.aHandlers.onnodeclose = $A();

		this.base(sHtml);
	}
});