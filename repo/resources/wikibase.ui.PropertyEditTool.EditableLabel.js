/**
 * JavasSript for managing editable representation of item labels.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableLabel.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author Tobias Gritschacher
 */
"use strict";

/**
 * Serves the input interface for an item label, extends EditableValue.
 * 
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableLabel = function( subject ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.call( this, subject );
};
window.wikibase.ui.PropertyEditTool.EditableLabel.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue();
$.extend( window.wikibase.ui.PropertyEditTool.EditableLabel.prototype, {
	
	_buildInterfaces: function( subject ) {
		var interfaces = window.wikibase.ui.PropertyEditTool.EditableValue.prototype._buildInterfaces.call( this, subject );
		interfaces[0].inputPlaceholder = mw.msg( 'wikibase-label-edit-placeholder' );
		
		interfaces[0].normalize = function( value ) {
			var value = window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype.normalize.call( this, value );
			value = value.replace( /\s+/g, ' ' ); // make sure we don't ever allow several spaces in the items label
			return value;
		};
		
		return interfaces;
	},
	
	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.prototype.getInputHelpMessage
	 */
	getInputHelpMessage: function() {
		return window.mw.msg( 'wikibase-label-input-help-message', mw.config.get('wbDataLangName') );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.prototype.getApiCallParams
	 */
	getApiCallParams: function( apiAction ) {
		return {
			action: "wbsetlanguageattribute",
			language: window.mw.config.get( 'wgUserLanguage' ),
			label: this.getValue().toString(),
			id: window.mw.config.get( 'wbItemId' ),
			item: 'set',
			token: window.mw.config.get( 'wbEditToken' )
		};
	}
} );
