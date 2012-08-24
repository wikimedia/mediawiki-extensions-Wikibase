/**
 * JavaScript for 'Wikibase' edit form for a items description
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.DescriptionEditTool.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */
"use strict";

/**
 * Module for 'Wikibase' extensions user interface functionality for editing the description of an item.
 */
window.wikibase.ui.DescriptionEditTool = function( subject ) {
	window.wikibase.ui.PropertyEditTool.call( this, subject );
};

window.wikibase.ui.DescriptionEditTool.prototype = new window.wikibase.ui.PropertyEditTool();
$.extend( window.wikibase.ui.DescriptionEditTool.prototype, {
	/**
	 * Initializes the edit form for the given h1 with 'firstHeading' class, basically the page title.
	 * This should normally be called directly by the constructor.
	 *
	 * @see wikibase.ui.PropertyEditTool._init()
	 */
	_init: function( subject ) {
		$( subject ).addClass( 'wb-ui-descriptionedittool' ); // add class specific to this ui element
		window.wikibase.ui.PropertyEditTool.prototype._init.call( this, subject );
	},

	getEditableValuePrototype: function() {
		return window.wikibase.ui.PropertyEditTool.EditableDescription;
	},

	allowsMultipleValues: false
} );
