/**
 * JavaScript for 'Wikibase' edit form for the heading representing the items label
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */
( function( mw, wb, $, undefined ) {
"use strict";
var $PARENT = wb.ui.PropertyEditTool;

/**
 * Module for 'Wikibase' extensions user interface functionality for editing the heading representing an items label.
 * @constructor
 * @see wb.ui.PropertyEditTool
 * @since 0.1
 */
wb.ui.LabelEditTool = wb.utilities.inherit( $PARENT, {
	/**
	 * Initializes the edit form for the given h1 with 'firstHeading' class, basically the page title.
	 * This should normally be called directly by the constructor.
	 *
	 * @see wikibase.ui.PropertyEditTool.init()
	 */
	init: function( subject ) {
		$( subject ).addClass( 'wb-ui-labeledittool' ); // add class specific to this ui element
		$PARENT.prototype.init.call( this, subject );
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
		return $PARENT.EditableLabel;
	},

	allowsMultipleValues: false
} );

} )( mediaWiki, wikibase, jQuery );
