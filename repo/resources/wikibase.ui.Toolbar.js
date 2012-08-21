/**
 * JavaScript for toolbars for 'Wikibase' property editing.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater
 * @author Tobias Gritschacher
 */
"use strict";

/**
 * Gives basic edit toolbar functionality, serves the "[edit]" button as well as the "[cancel|save]"
 * buttons and other related stuff.
 */
window.wikibase.ui.Toolbar = function() {
	this._init();
};
window.wikibase.ui.Toolbar.prototype = {
	/**
	 * @const
	 * Class which marks the element within the site html.
	 */
	UI_CLASS: 'wb-ui-toolbar',

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
	 * items that are rendered inside the toolbar like buttons, labels, tooltips or groups of such items
	 */
	_items: null,

	/**
	 * @var string
	 * initial css display value that is stored for re-showing when hiding the toolbar
	 */
	_display: null,

	/**
	 * Initializes the edit toolbar for the given element.
	 * This should normally be called directly by the constructor.
	 */
	_init: function() {
		if( this._elem !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this._items = [];
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

	appendTo: function( elem ) {
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
			parent = this._elem.parent();
			this._elem.remove(); // remove element after parent is known
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
		var i = -1;
		for( i in this._items ) {
			if( this.renderItemSeparators && i != 0 ) {
				this._elem.append( '|' );
			}
			this._elem.append( this._items[i]._elem );
		}

		// only render brackets if we have any content
		if( this.renderItemSeparators && i > -1 ) {
			this._elem
			.prepend( '[' )
			.append( ']' );
		}
	},

	/**
	 * This will add a toolbar element, e.g. a label or a button to the toolbar at the given index.
	 *
	 * @param Object elem toolbar content element (e.g. a group, button or label).
	 * @param index where to add the element (use negative values to specify the position from the end).
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
	 *
	 * @param Object elem the element to remove
	 * @return bool false if element isn't part of this element
	 */
	removeElement: function( elem ) {
		var index = this.getIndexOf( elem );
		if( index < 0 ) {
			return false;
		}
		this._items.splice( index, 1 );

		this.draw(); // TODO: could be more efficient when just removing one element
		return true;
	},

	/**
	 * Returns whether the given element is represented within the toolbar.
	 *
	 * @return bool
	 */
	hasElement: function( elem ) {
		return this.getIndexOf( elem ) > -1;
	},

	/**
	 * returns the index of an element within the toolbar, -1 in case the element is not represented.
	 *
	 * @return int
	 */
	getIndexOf: function( elem ) {
		return $.inArray( elem, this._items );
	},

	/**
	 * Determine whether the state (disabled, enabled) of any toolbar element can be changed.
	 *
	 * @return bool whether the state of any toolbar element can be changed
	 */
	isStateChangeable: function() {
		var stateChangeable = false;
		$.each( this._items, function( i, item ) {
			if ( item.isStateChangeable() ) {
				stateChangeable = true;
			}
		} );
		return stateChangeable;
	},

	/**
	 * Convenience method to disable all toolbar elements.
	 *
	 * @return whether all elements are disabled
	 */
	disable: function() {
		return this.setDisabled( true );
	},

	/**
	 * Convenience method to enable all toolbar elements.
	 *
	 * @return bool whether all elements are enabled
	 */
	enable: function() {
		return this.setDisabled( false );
	},

	/**
	 * Dis- or enable all toolbar elements.
	 *
	 * @param bool disable true to disable, false to enable all toolbar elements
	 * @return bool whether the operation was successful
	 */
	setDisabled: function( disable ) {
		var success = true;
		$.each( this._items, function( i, item ) {
			success = item.setDisabled( disable ) && success;
		} );
		return success;
	},

	/**
	 * check whether all toolbar elements are disabled
	 *
	 * @return bool whether all toolbar elements are disabled
	 */
	isDisabled: function() {
		var state = this.getElementsState();
		return ( state === wikibase.ui.ELEMENT_STATE.DISABLED );
	},

	/**
	 * check whether all toolbar elements are enabled
	 *
	 * @return bool whether all toolbar elements are enabled
	 */
	isEnabled: function() {
		var state = this.getElementsState();
		return ( state === wikibase.ui.ELEMENT_STATE.ENABLED );
	},

	/**
	 * get state of all elements (disabled, enabled or mixed)
	 *
	 * @return number whether all elements are enabled (true), disabled (false) or have mixed states
	 */
	getElementsState: function() {
		var disabled = true, enabled = true;
		$.each( this._items, function( i, item ) {
			// loop through all sub-toolbars and check dedicated toolbar elements
			if ( item instanceof wikibase.ui.Toolbar || item.stateChangeable ) {
				if ( item.isDisabled() ) {
					enabled = false;
				} else if ( !item.isDisabled() ) {
					disabled = false;
				}
			}
		} );
		if ( disabled === true ) {
			return wikibase.ui.ELEMENT_STATE.DISABLED;
		} else if ( enabled === true ) {
			return wikibase.ui.ELEMENT_STATE.ENABLED;
		} else {
			return wikibase.ui.ELEMENT_STATE.MIXED;
		}
	},

	destroy: function() {
		if( this._items !== null ) {
			for( var i in this._items ) {
				this._items[i].destroy();
			}
			this._items = null;
		}
		if( this._elem !== null ) {
			this._elem.remove();
			this._elem = null;
		}
	},

	/**
	 * hide the toolbar
	 *
	 * @return bool whether toolbar is hidden
	 */
	hide: function() {
		if ( this._display === null || this._display === 'none' ) {
			this._display = this._elem.css( 'display' );
		}
		this._elem.css( 'display', 'none' );
		return this.isHidden();
	},

	/**
	 * show the toolbar
	 *
	 * @return whether toolbar is visible
	 */
	show: function() {
		this._elem.css( 'display', ( this._display === null ) ? 'block' : this._display );
		return !this.isHidden();
	},

	/**
	 * determine whether this toolbar is hidden
	 *
	 * @return bool
	 */
	isHidden: function() {
		return ( this._elem.css( 'display' ) == 'none' );
	},

	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * Defines whether the toolbar should be displayed with separators "|" between each item. In that
	 * case everything will also be wrapped within "[" and "]".
	 * This is particulary interesting for wikibase.ui.Toolbar.Group toolbar groups
	 * @var bool
	 */
	renderItemSeparators: false
};
