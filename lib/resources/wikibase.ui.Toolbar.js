/**
 * JavaScript for toolbars used in 'Wikibase' extensions.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater
 */
( function( mw, wb, $ ) {
'use strict';

/**
 * Gives basic edit toolbar functionality, serves the "[edit]" button as well as the "[cancel|save]"
 * buttons and other related stuff.
 * @constructor
 * @extends wb.utilities.ObservableObject
 * @since 0.1
 */
wb.ui.Toolbar = function( uiClass ) {
	this.init( uiClass );
};
$.extend( wb.ui.Toolbar.prototype, {
	/**
	 * @const
	 * Class which marks the element within the site html.
	 */
	UI_CLASS: 'wb-ui-toolbar',

	/**
	 * additional css class to be assigned to the toolbar container
	 * @type String
	 */
	_additionalUiClass: '',

	/**
	 * @type jQuery
	 */
	_parent: null,

	/**
	 * The toolbar element in the dom
	 * @type jQuery
	 */
	_elem: null,

	/**
	 * @type Array
	 * items that are rendered inside the toolbar like buttons, labels, tooltips or groups of such items
	 */
	_items: null,

	/**
	 * @type string
	 * initial css display value that is stored for re-showing when hiding the toolbar
	 */
	_display: null,

	/**
	 * Initializes the edit toolbar for the given element.
	 * This should normally be called directly by the constructor.
	 */
	init: function( uiClass ) {
		if( this._elem !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this._items = [];
		if ( typeof uiClass === 'string' ) {
			this._additionalUiClass = uiClass + '-toolbar';
		}
		this.draw(); // draw first to have toolbar wrapper
		this._initToolbar();
		this._elem.data( 'wb-toolbar', this );
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
		this._elem = $( '<span/>', {
			'class': this.UI_CLASS + ' ' + this._additionalUiClass
		} ).data( 'wb-toolbar', this );

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
			i = parseInt( i, 10 );
			if( this.renderItemSeparators && i !== 0 ) {
				this._elem.append( '|' );
			}
			this._elem.append(
				( this._items[i] instanceof $ ) ? this._items[i] : this._items[i]._elem
			);
		}

		// only render brackets if we have any content
		if( this.renderItemSeparators && i > -1 ) {
			this._elem
			.prepend( '[' )
			.append( ']' );
		}
	},

	/**
	 * Returns all toolbar elements of this toolbar (e.g. labels, buttons, etc.)
	 * @since 0.2
	 *
	 * @return wb.ui.Toolbar.Label[]
	 */
	getElements: function() {
		return this._items.slice(); // don't allow direct access to internal array to outside world
	},

	/**
	 * This will add a toolbar element, e.g. a label or a button to the toolbar at the given index.
	 *
	 * @param {Object} elem toolbar content element (e.g. a group, button or label).
	 * @param {Number} index where to add the element (use negative values to specify the position
	 *        from the end).
	 */
	addElement: function( elem, index ) {
		// check whether exact same element is displayed in toolbar already:
		var existingIndex = this.getIndexOf( elem );

		// calculate actual index if negative index is given:
		if( index !== undefined && index < 0 ) {
			index = this.getLength() - index;
			index = index < 0 ? 0 : index;
		}

		if( existingIndex === index ) {
			return; // displayed where it is supposed to be, do nothing
		}

		if( existingIndex > -1 ) {
			// displayed in some other place, remove and insert again in next step
			this._items.splice( existingIndex, 1 );
		}

		if( index === undefined ) {
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
	 * @param {Object} elem the element to remove
	 * @return {Boolean} false if element isn't part of this element
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
	 * @return Boolean
	 */
	hasElement: function( elem ) {
		return this.getIndexOf( elem ) > -1;
	},

	/**
	 * returns the index of an element within the toolbar, -1 in case the element is not represented.
	 *
	 * @return Number
	 */
	getIndexOf: function( elem ) {
		return $.inArray( elem, this._items );
	},

	/**
	 * Returns how many items are displayed in this toolbar.
	 * @since 0.3
	 *
	 * @return {Number}
	 */
	getLength: function() {
		return this._items.length;
	},

	/**
	 * Determine whether the state (disabled, enabled) of any toolbar element can be changed.
	 *
	 * @return {Boolean} whether the state of any toolbar element can be changed
	 */
	isStateChangeable: function() {
		for( var i = 0; i < this._items.length; i++ ) {
			if( this._getFunctionLayer( this._items[i] ).isStateChangeable() ) {
				return true;
			}
		}
		return false;
	},

	/**
	 * Gets the function layer of an item.
	 *
	 * @since 0.4
	 *
	 * @param {*} item
	 * @return {*}
	 * @deprecated
	 * @todo Toolbar items should be jQuery nodes. If they are featuring widgets these should be
	 *  accessed by a generic data attribute instead of using this method.
	 */
	_getFunctionLayer: function( item ) {
		if( item instanceof $ ) {
			if( item.data( 'toolbarlabel' ) ) {
				return item.data( 'toolbarlabel' );
			} else if( item.data( 'toolbarbutton' ) ) {
				return item.data( 'toolbarbutton' );
			}
		}
		return item;
	},

	destroy: function() {
		if( this._items !== null ) {
			for( var i in this._items ) {
				this._getFunctionLayer( this._items[i] ).destroy();
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
	 * @return {Boolean} whether toolbar is hidden
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
	 * @return {Boolean} whether toolbar is visible
	 */
	show: function() {
		this._elem.css( 'display', ( this._display === null ) ? 'block' : this._display );
		return !this.isHidden();
	},

	/**
	 * determine whether this toolbar is hidden
	 *
	 * @return Boolean
	 */
	isHidden: function() {
		return ( this._elem.css( 'display' ) === 'none' );
	},

	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * Defines whether the toolbar should be displayed with separators "|" between each item. In that
	 * case everything will also be wrapped within "[" and "]".
	 * This is particulary interesting for wikibase.ui.Toolbar.Group toolbar groups
	 * @type Boolean
	 */
	renderItemSeparators: false
} );

// add disable/enable functionality overwriting required functions
wb.utilities.ui.StatableObject.useWith( wb.ui.Toolbar, {
	/**
	 * Determines the state (disabled, enabled or mixed) of all toolbar elements.
	 * @see wb.utilities.ui.StatableObject.getState
	 */
	getState: function() {
		var self = this,
			state;
		$.each( this._items, function( i, item ) {
			if( !self._getFunctionLayer( item ).isStateChangeable() ) {
				return true; // ignore element if state is locked at the moment
			}
			var currentState = self._getFunctionLayer( item ).getState();

			if( state !== currentState) {
				if( state === undefined ) {
					state = currentState;
				} else {
					// state of this element different from others -> mixed state
					state = self.STATE.MIXED;
					return false; // no point in checking other states, we are mixed!
				}
			}
		} );
		if( state === undefined ) {
			// TODO/FIXME: This is quite ugly: Assume toolbar.disable(), remove last button, toolbar.getState()
			//             which would then return enabled instead of disabled.
			return this.STATE.ENABLED;
		}
		return state;
	},

	/**
	 * @see wb.utilities.ui.StatableObject._setState
	 */
	_setState: function( state ) {
		var success = true;
		for( var i = 0; i < this._items.length; i++ ) {
			success = this._getFunctionLayer( this._items[i] ).setState( state ) && success;
		}
		return success;
	}

} );

// add direct event handling:
wb.utilities.ObservableObject.useWith( wb.ui.Toolbar );

} )( mediaWiki, wikibase, jQuery );
