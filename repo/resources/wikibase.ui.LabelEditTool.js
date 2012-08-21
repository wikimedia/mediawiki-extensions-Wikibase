/**
 * JavaScript for 'Wikibase' edit form for the heading representing the items label
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */
"use strict";

/**
 * Module for 'Wikibase' extensions user interface functionality for editing the heading representing
 * an items label.
 */
window.wikibase.ui.LabelEditTool = function( subject ) {
	window.wikibase.ui.PropertyEditTool.call( this, subject );
};

window.wikibase.ui.LabelEditTool.prototype = new window.wikibase.ui.PropertyEditTool();
$.extend( window.wikibase.ui.LabelEditTool.prototype, {
	/**
	 * Initializes the edit form for the given h1 with 'firstHeading' class, basically the page title.
	 * This should normally be called directly by the constructor.
	 *
	 * @see wikibase.ui.PropertyEditTool._init()
	 */
	_init: function( subject ) {
		$( subject ).addClass( 'wb-ui-labeledittool' ); // add class specific to this ui element
		window.wikibase.ui.PropertyEditTool.prototype._init.call( this, subject );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._getValueElems()
	 */
	_getValueElems: function() {
		return this._subject.children( 'h1.wb-firstHeading span' );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.getPropertyName()
	 *
	 * @return string 'label'
	 */
	getPropertyName: function() {
		return 'label';
	},

	getEditableValuePrototype: function() {
		return window.wikibase.ui.PropertyEditTool.EditableLabel;
	},

	allowsMultipleValues: false
} );
