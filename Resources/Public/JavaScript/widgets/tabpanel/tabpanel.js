Formidable.Classes.TabPanel = Formidable.Classes.RdtBaseClass.extend({
	oTabPanel: null,
	constructor: function(oConfig) {
		this.base(oConfig);
		this.oTabPanel = new Control.Tabs(
			this.domNode(),
			this.config.libconfig
		);
	},
	next: function() { this.oTabPanel.next();},
	previous: function() { this.oTabPanel.previous();},
	first: function() { this.oTabPanel.first();},
	last: function() { this.oTabPanel.last();},
	setActiveTab: function(sTabId) { this.oTabPanel.setActiveTab(sTabId);}
});