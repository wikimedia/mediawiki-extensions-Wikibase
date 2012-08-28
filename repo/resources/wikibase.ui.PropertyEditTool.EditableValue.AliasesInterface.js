/**
 * JavaScript for a part of an items editable aliases
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $, undefined ) {
"use strict";
var $PARENT = wb.ui.PropertyEditTool.EditableValue.ListInterface;

/**
 * Serves the input interface for an items aliases and handles the conversion between the pure html representation
 * and the interface itself in both directions.
 * @constructor
 * @see wikibase.ui.PropertyEditTool.EditableValue.ListInterface
 * @since 0.1
 */
wb.ui.PropertyEditTool.EditableValue.AliasesInterface = wb.utilities.inherit( $PARENT, {
	/**
	 * @see wikibase.ui.PropertyEditTool.ListInterface.UI_VALUE_PIECE_CLASS
	 * @const
	 */
	UI_VALUE_PIECE_CLASS: 'wb-aliases-alias',

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.inputPlaceholder
	 * @var string
	 */
	inputPlaceholder: mw.msg( 'wikibase-alias-edit-placeholder' )
} );

} )( mediaWiki, wikibase, jQuery );
