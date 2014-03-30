/**
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
( function( mw, wb, util, $ ) {
'use strict';
/* jshint camelcase: false */

var PARENT = wb.ui.PropertyEditTool.EditableValue.ListInterface;

/**
 * Serves the input interface for the badges of a site link.
 * @constructor
 * @see wikibase.ui.PropertyEditTool.EditableValue.ListInterface
 * @since 0.5
 */
wb.ui.PropertyEditTool.EditableValue.SiteBadgesInterface = util.inherit( PARENT, {
	/**
	 * @see wikibase.ui.PropertyEditTool.ListInterface.UI_VALUE_PIECE_CLASS
	 * @const
	 */
	UI_VALUE_PIECE_CLASS: 'wb-sitelinks-badges-badge',

	// @todo: this must handle the badges we allow in the config
} );

} )( mediaWiki, wikibase, util );
