/**
 * JavaScript for 'Wikibase' edit form for a items description
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */
( function( mw, wb, $, undefined ) {
'use strict';
var PARENT = wb.ui.PropertyEditTool;

/**
 * Module for 'Wikibase' extensions user interface functionality for editing the description of an item.
 *
 * @constructor
 * @see wb.ui.PropertyEditTool
 * @since 0.1
 */
wb.ui.DescriptionEditTool = wb.utilities.inherit( PARENT, {
	/**
	 * @see wb.ui.SECONDARY_UI_CLASSES
	 */
	SECONDARY_UI_CLASSES: PARENT.prototype.SECONDARY_UI_CLASSES + ' wb-ui-descriptionedittool',

	/**
	 * @see wb.ui.PropertyEditTool._init()
	 */
	_init: function( subject, options ) {
		// setting default options
		options = $.extend( {}, PARENT.prototype._options, {
			/**
			 * @see wikibase.ui.PropertyEditTool.allowsMultipleValues
			 */
			allowsMultipleValues: false
		} );
		PARENT.prototype._init.call( this, subject, options );
	},

	/**
	 * @see wb.ui.PropertyEditTool.getEditableValuePrototype
	 */
	getEditableValuePrototype: function() {
		return wb.ui.PropertyEditTool.EditableDescription;
	}

} );

} )( mediaWiki, wikibase, jQuery );
