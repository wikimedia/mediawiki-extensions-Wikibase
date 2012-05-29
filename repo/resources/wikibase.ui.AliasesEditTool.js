/**
 * JavasSript for 'Wikibase' edit form for an items aliases
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.AliasesEditTool.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */
"use strict";

/**
 * Module for 'Wikibase' extensions user interface functionality for editing an items aliases.
 *
 * @since 0.1
 */
window.wikibase.ui.AliasesEditTool = function( subject ) {
	window.wikibase.ui.PropertyEditTool.call( this, subject );
};

window.wikibase.ui.AliasesEditTool.prototype = new window.wikibase.ui.PropertyEditTool();
$.extend( window.wikibase.ui.AliasesEditTool.prototype, {
	/**
	 * Initializes the edit form for the aliases.
	 * This should normally be called directly by the constructor.
	 *
	 * @see wikibase.ui.PropertyEditTool._init
	 */
	_init: function( subject ) {
		// call prototypes _init():
		window.wikibase.ui.PropertyEditTool.prototype._init.call( this, subject );
		// add class specific to this ui element:
		this._subject.addClass( 'wb-ui-aliasesedittool' );
	},
	
	/**
	 * @see wikibase.ui.PropertyEditTool._getValueElems
	 */
	_getValueElems: function() {
		return this._subject.children( '.wb-aliases-container:first' );
	},
	
	/**
	 * @see wikibase.ui.PropertyEditTool.getPropertyName
	 * 
	 * @return string 'label'
	 */
	getPropertyName: function() {
		return 'label';
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.getEditableValuePrototype
	 */
	getEditableValuePrototype: function() {
		return window.wikibase.ui.PropertyEditTool.EditableAliases;
	},
	
	allowsMultipleValues: false
} );
