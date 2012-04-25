/**
 * JavasSript for managing editable representation of an items description.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author Tobias Gritschacher
 */
"use strict";

/**
 * Serves the input interface for an item description, extends EditableValue.
 * 
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableDescription = function( subject ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.call( this, subject );
};
window.wikibase.ui.PropertyEditTool.EditableDescription.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue();
$.extend( window.wikibase.ui.PropertyEditTool.EditableDescription.prototype, {
	
	_buildInterfaces: function( subject ) {
		var interfaces = window.wikibase.ui.PropertyEditTool.EditableValue.prototype._buildInterfaces.call( this, subject );
		interfaces[0].inputPlaceholder = mw.msg( 'wikibase-description-edit-placeholder' );
		
		return interfaces;
	},
	
	getInputHelpMessage: function() {
		return window.mw.msg( 'wikibase-description-input-help-message', mw.config.get('wbDataLangName') );
	},

	getApiCallParams: function() {
		return {
			action: 'wbsetlanguageattribute',
			language: wgUserLanguage,
			description: this.getValue().toString(),
			id: mw.config.values.wbItemId,
			item: 'set'
		};
	}
} );
