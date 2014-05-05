(function() {

	var ProjectEditor = require("./edit-project.js");

	var Site = {
		init: function() {
			var editPage = document.querySelector('[data-edit-project]');

			if (editPage) {
				ProjectEditor.init();
			}
		},
		start: function() {
			document.addEventListener("DOMContentLoaded", this, false);
		},
		handleEvent: function(e) {
			switch(e.type) {
				case "DOMContentLoaded":
					this.init();
					break;
			}
		}
	};
 
	if (document.readyState !== "complete") {
		Site.start();	
	} else {
		Site.init();
	}
	
})();