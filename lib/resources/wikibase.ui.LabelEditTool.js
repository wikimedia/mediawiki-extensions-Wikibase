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
'use strict';
var $PARENT = wb.ui.PropertyEditTool;

/**
 * Module for 'Wikibase' extensions user interface functionality for editing the heading representing an items label.
 * @constructor
 * @see wb.ui.PropertyEditTool
 * @since 0.1
 */
wb.ui.LabelEditTool = wb.utilities.inherit( $PARENT, {
	/**
	 * @see wikibase.ui.SECONDARY_UI_CLASSES
	 */
	SECONDARY_UI_CLASSES: $PARENT.prototype.SECONDARY_UI_CLASSES + ' wb-ui-labeledittool',

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
