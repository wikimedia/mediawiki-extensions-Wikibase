/**
 * JavaScript for a part of an items editable aliases
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
'use strict';

/**
 * Serves the input interface for an items aliases and handles the conversion between the pure html representation
 * and the interface itself in both directions.
 *
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableValue.AliasesInterface = function( subject ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.ListInterface.apply( this, arguments );
};
window.wikibase.ui.PropertyEditTool.EditableValue.AliasesInterface.prototype
	= Object.create( window.wikibase.ui.PropertyEditTool.EditableValue.ListInterface.prototype );
$.extend( window.wikibase.ui.PropertyEditTool.EditableValue.AliasesInterface.prototype, {
	/**
	 * @see wikibase.ui.PropertyEditTool.ListInterface.UI_VALUE_PIECE_CLASS
	 * @const
	 */
	UI_VALUE_PIECE_CLASS: 'wb-aliases-alias',

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.inputPlaceholder
	 * @var string
	 */
	inputPlaceholder: window.mw.msg( 'wikibase-alias-edit-placeholder' )
} );

