/**
 * JavasSript for toolbars for 'Wikibase' property editing.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.Toolbar.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater
 * @author Tobias Gritschacher
 */

/**
 * Gives basic edit toolbar functionality, serves the "[edit]" button as well as the "[cancel|save]"
 * buttons and other related stuff.
 */
window.wikibase.ui.PropertyEditTool.Toolbar = function() {
	this._init();
};
window.wikibase.ui.PropertyEditTool.Toolbar.prototype = {
	/**
	 * @const
	 * Class which marks the element within the site html.
	 */
	UI_CLASS: 'wb-ui-propertyedittoolbar',
	
	/**
	 * @var jQuery
	 */
	_parent: null,
	
	/**
	 * The toolbar element in the dom
	 * @var jQuery
	 */
	_elem: null,
	
	/**
	 * @var Array
	 */
	_items: null,
	
	/**
	 * Initializes the edit toolbar for the given element.
	 * This should normally be called directly by the constructor.
	 */
	_init: function() {
		if( this._elem !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this._items = new Array();
		//this._parent = parent;
		this.draw(); // draw first to have toolbar wrapper
		this._initToolbar();
	},
	
	/**
	 * Initializes elements within the toolbar if any should be there from the beginning.
	 */
	_initToolbar: function() {},
	
	/**
	 * Function for (re)rendering the element
	 */
	draw: function() {
		this._drawToolbar();
		this._drawToolbarElements();
	},
	
	appendTo: function( elem ) { // TODO: integrate the whole prototype with jQuery somehow
		if( this._elem === null ) {
			this.draw(); // this will generate the toolbar
		}
		this._elem.appendTo( elem );
		this._parent = this._elem.parent();
	},
	
	/**
	 * Draws the toolbar element itself without its content
	 */
	_drawToolbar: function() {
		var parent = null;
		if( this._elem !== null ) {
			this._elem.children().detach(); // only detach so elements can be attached somewhere else
			this._elem.remove();
			parent = this._elem.parent();
		}
		this._elem = $( '<div/>', {
			'class': this.UI_CLASS
		} );
	   	
		if( parent !== null ) { // if not known yet, appendTo() wasn't called so far
			parent.append( this._elem );
		}
	},
	
	/**
	 * Draws the toolbar elements like buttons and labels
	 */
	_drawToolbarElements: function() {
		for( var i in this._items ) {
			this._elem.append( this._items[i]._elem );
		}
	},
	
	/**
	 * This will add a toolbar element, e.g. a label or a button to the toolbar at the given index.
	 * 
	 * @param Object elem toolbar content element (e.g. a group, button or label).
	 * @param index where to add the element. 0 will 
	 */
	addElement: function( elem, index ) {
		if( typeof index == 'undefined' ) {
			// add elem as last one
			this._items.push( elem );
		} else {
			// add elem at certain index
			this._items.splice( index, 0, elem);
		}
		this.draw(); // TODO: could be more efficient when just adding one element
	},
	
	/**
	 * Removes an element from the toolbar
	 * @param Object elem the element to remove
	 * @return bool false if element isn't part of this element
	 */
	removeElement: function( elem ) {
		$index = $.inArray( elem, this._items );
		if( $index === -1 ) {
			return false;
		}
		this._items.splice( $index, 1 );
		
		this.draw(); // TODO: could be more efficient when just removing one element
	},

	destroy: function() {
		if( this._items !== null ) {
			for( var i in this._items ) {
				this._items[i].destroy();
			}
		}
		if( this._elem !== null ) {
			this._elem.remove();
		}
	}
};
