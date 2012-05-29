/**
 * JavasSript for managing editable representation of an items description.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableDescription.js
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

	API_KEY: 'descriptions',

	_buildInterfaces: function( subject ) {
		var interfaces = window.wikibase.ui.PropertyEditTool.EditableValue.prototype._buildInterfaces.call( this, subject );
		interfaces[0].inputPlaceholder = mw.msg( 'wikibase-description-edit-placeholder' );
		interfaces[0].autoExpand = true;
		
		return interfaces;
	},
	
	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.prototype.getInputHelpMessage
	 */
	getInputHelpMessage: function() {
		return window.mw.msg( 'wikibase-description-input-help-message', mw.config.get('wbDataLangName') );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.prototype.getApiCallParams
	 */
	getApiCallParams: function( apiAction ) {
		var params = window.wikibase.ui.PropertyEditTool.EditableValue.prototype.getApiCallParams.call( this, apiAction );
		return $.extend( params, {
			action: 'wbsetlanguageattribute',
			description: this.getValue().toString()
		} );
	}
} );
