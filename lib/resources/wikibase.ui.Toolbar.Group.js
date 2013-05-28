/**
 * JavaScript for 'Wikibase' property edit tool toolbar groups
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $ ) {
'use strict';
var PARENT = wb.ui.Toolbar;

/**
 * Represents a group of toolbar elements within a toolbar
 * @constructor
 * @see wb.ui.Toolbar
 * @since 0.1
 */
wb.ui.Toolbar.Group = function() {
	this.init();
};
wb.ui.Toolbar.Group = wb.utilities.inherit( PARENT, {

	UI_CLASS: 'wb-ui-toolbar-group',

	init: function() {
		PARENT.prototype.init.call( this );
	},

	_drawToolbar: function() {
		if( this._elem === null ) {
			// create outer span for group the first time only
			this._elem = $( '<span/>', {
				'class': this.UI_CLASS
			} );
		}
		else {
			// empty content of the group but keep group since it might be attached to a toolbar alreaedy!
			this._elem.children().detach();
			this._elem.empty();
		}
	},

	destroy: function() {
		PARENT.prototype.destroy.call( this );
	},

	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * @see wb.ui.Toolbar.Group.renderItemSeparators
	 */
	renderItemSeparators: true
} );

} )( mediaWiki, wikibase, jQuery );

