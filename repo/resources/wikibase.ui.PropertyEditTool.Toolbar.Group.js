/**
 * JavasSript for 'Wikibase' property edit tool toolbar groups
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.Toolbar.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
"use strict";

/**
 * Represents a group of toolbar elements within a toolbar
 */
window.wikibase.ui.PropertyEditTool.Toolbar.Group = function() {
	this._init();
};
window.wikibase.ui.PropertyEditTool.Toolbar.Group.prototype = new window.wikibase.ui.PropertyEditTool.Toolbar();
$.extend( window.wikibase.ui.PropertyEditTool.Toolbar.Group.prototype, {

	UI_CLASS: 'wb-ui-propertyedittoolbar-group',
	
	_elem: null, // TODO: Override this because draw() in parent will initialize it! Overthink inheritance model/constructor
	
	_init: function( elements ) {
		window.wikibase.ui.PropertyEditTool.Toolbar.prototype._init.call( this );
	},	
	
	_drawToolbar: function() {
		if( this._elem === null ) {
			// create outer div for group only the first time
			this._elem = $( '<div/>', {
				'class': this.UI_CLASS
			} );
		}
		else {
			// empty content of the group but keep group since it might be attached to a toolbar alreaedy!
			this._elem.children().detach();
			this._elem.empty();
		}
	},
	
	_drawToolbarElements: function() {
		for( var i in this._items ) {
			if( this.displayGroupSeparators && i != 0 ) {
				this._elem.append( '|' );
			}
			this._elem.append( this._items[i]._elem );
		}
		
		if( this.displayGroupSeparators ) {
			this._elem
			.prepend( '[' )
			.append( ']' );
		}
	},

	destroy: function() {
		window.wikibase.ui.PropertyEditTool.Toolbar.prototype.destroy.call( this );
	},
	
	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * Defines whether the group should be displayed with separators "|" between ach items. In that case
	 * everything will also be wrapped within "[" and "]".
	 * @var bool
	 */
	displayGroupSeparators: true
} );
