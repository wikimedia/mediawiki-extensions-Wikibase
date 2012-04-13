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
 * 
 * @param jQuery parent
 */
window.wikibase.ui.PropertyEditTool.Toolbar = function( parent ) {
	if( typeof parent != 'undefined' ) {
		this._init( parent );
	}
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
	 * 
	 * @param parent the element holding the toolbar
	 */
	_init: function( parent ) {
		if( this._parent !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this._items = new Array();
		this._parent = parent;
		this.draw(); // draw first to have toolbar wrapper
		this._initToolbar();
	},
	
	_initToolbar: function() {
		
	},
	
	/**
	 * Function for (re)rendering the element
	 */
	draw: function() {
		this._drawToolbar();
		this._drawToolbarElements();
	},
	
	/**
	 * Draws the toolbar element itself without its content
	 */
	_drawToolbar: function() {		
		if( this._elem !== null ) {
			this._elem.children().detach(); // only detach so elements can be attached somewhere else
			this._elem.remove();
		}
		this._elem = $( '<div/>', {
			'class': this.UI_CLASS
		} );
	   
		// TODO: check whether this is the proper way of doing the rtl thing
		this._parent.append( this._elem );
		/*
		if( $( 'body' ).hasClass( 'rtl' ) ) {
			this._parent.prepend( this._elem );
		} else {
			this._parent.append( this._elem );
		}
		*/
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
	 * @param elem
	 * @param index TODO: not implemented yet
	 */
	addElement: function( elem, index ) {
		// TODO: add index functionality!
		//this._elem.append( elem._elem );
		this._items.push( elem );		
		this.draw(); // TODO: make this more efficient
	},
	
	/**
	 * Removes an element from the toolbar
	 * @param elem the element to remove
	 * @return bool false if element isn't part of this element
	 */
	removeElement: function( elem ) {
		$index = $.inArray( elem, this._items );
		if( $index === -1 ) {
			return false;
		}
		this._items.splice( $index, 1 );
		
		this.draw(); // TODO: make this more efficient
	},

	destroy: function() {
		// TODO
	}
};
