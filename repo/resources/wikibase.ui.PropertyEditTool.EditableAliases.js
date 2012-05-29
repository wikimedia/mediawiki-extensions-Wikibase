/**
 * JavasSript for managing editable representation of item aliases. This is for editing a whole set of aliases, not just
 * a single one.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableAliases.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
"use strict";

/**
 * Serves the input interface for an items aliases, extends EditableValue.
 * 
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableAliases = function( subject ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.call( this, subject );
};
window.wikibase.ui.PropertyEditTool.EditableAliases.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue();
$.extend( window.wikibase.ui.PropertyEditTool.EditableAliases.prototype, {

	API_KEY: 'aliases',

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._init
	 */
	_init: function( subject, toolbar ) {
		var newSubject = $( '<span>' );
		$( subject ).replaceWith( newSubject ).appendTo( newSubject );

		return window.wikibase.ui.PropertyEditTool.EditableValue.prototype._init.call( this, newSubject, toolbar );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._buildInterfaces
	 */
	_buildInterfaces: function( subject ) {
		var interfaces = new Array();

		var interfaceParent = $( '<div/>' );
		subject.children( 'ul:first' ).replaceWith( interfaceParent ).appendTo( interfaceParent );

		interfaces.push( new wikibase.ui.PropertyEditTool.EditableValue.AliasesInterface( interfaceParent, this ) );

		return interfaces;
	},
	
	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.getInputHelpMessage
	 */
	getInputHelpMessage: function() {
		return window.mw.msg( 'wikibase-label-input-help-message', mw.config.get('wbDataLangName') );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.getApiCallParams
	 */
	getApiCallParams: function( apiAction ) {
		var params = window.wikibase.ui.PropertyEditTool.EditableValue.prototype.getApiCallParams.call( this, apiAction );
		return $.extend( params, {
			action: "wbsetlanguageattribute",
			label: this.getValue().toString()
		} );
	}
} );
