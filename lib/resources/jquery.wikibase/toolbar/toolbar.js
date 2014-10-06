/**
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner
 */
( function( wb, $ ) {
'use strict';

var PARENT = $.Widget;

/**
 * Toolbar widget that can be filled with compatible nodes that feature wikibase toolbar items.
 * These are label, button and toolbar which may be used as a subgroup. Compatible nodes have to
 * feature a "wikibase-toolbaritem" data attribute that references a jQuery widget.
 * TODO: Implement jQuery.wikibase.toolbaritem base class.
 *
 * @constructor
 * @since 0.4
 *
 * @option {boolean} renderItemSeparators: Defines whether the toolbar should be displayed with
 *         separators "|" between each item. In that case everything will also be wrapped within "["
 *         and "]". This is particularly interesting for jQuery.wikibase.toolbareditgroup instances.
 */
$.widget( 'wikibase.toolbar', PARENT, {
	/**
	 * Options.
	 * @type {Object}
	 */
	options: {
		renderItemSeparators: false
	},

	/**
	 * Items that are rendered inside the toolbar like buttons, labels, tooltips or groups of such
	 * items.
	 * @type {jQuery[]}
	 */
	_items: null,

	/**
	 * Initial css display value that is stored for re-showing when hiding the toolbar.
	 * @type {string}
	 * TODO: Along with hide(), show() and isHidden() method, evaluate if still needed.
	 */
	_display: null,

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		PARENT.prototype._create.call( this );

		this.element
		.addClass( this.widgetFullName )
		.data( 'wikibase-toolbaritem', this );

		this._items = [];
		this.draw(); // draw first to have toolbar wrapper
	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		if( this._items !== null ) {
			for( var i in this._items ) {
				this._items[i].data( 'wikibase-toolbaritem' ).destroy();
			}
			this._items = null;
		}

		this.element
		.removeClass( this.widgetFullName )
		.removeData( 'wikibase-toolbaritem' )
		.empty();

		PARENT.prototype.destroy.call( this );
	},

	/**
	 * Re(renders) the toolbar element.
	 * @since 0.4
	 */
	draw: function() {
		// Detach instead of emptying to not loose widget instances:
		this.detach();

		// Draw toolbar items:
		var i = -1;
		for( i in this._items ) {
			i = parseInt( i, 10 );
			if( this.options.renderItemSeparators && i !== 0 ) {
				this.element.append( '|' );
			}

			var toolbarItemWidget = this._items[i].data( 'wikibase-toolbaritem' );
			if( toolbarItemWidget instanceof $.wikibase.toolbar ) {
				// Toolbar group.
				this.element.append( toolbarItemWidget.element );
				toolbarItemWidget.draw();
			} else {
				this.element.append( this._items[i] );
			}
		}

		// Only render brackets if there is any content:
		if( this.options.renderItemSeparators && i > -1 ) {
			this.element
			.prepend( '[' )
			.append( ']' );
		}
	},

	/**
	 * Detaches the toolbar children from the DOM without destroying them. By detaching, widget
	 * instances do not get lost which is important when redrawing the toolbar.
	 * @since 0.4
	 * TODO: This method should not be public. Even more, the draw() method should be improved in
	 *  order to not have to detach the children.
	 */
	detach: function() {
		this.element.children().each( function( i, node ) {
			var $node = $( node );
			if( $node.data( 'toolbar' ) ) {
				$node.data( 'toolbar' ).detach();
			}
			$node.detach();
		} );
		this.element.empty(); // Throw away text nodes (separators).
	},

	/**
	 * Returns all toolbar elements of this toolbar (e.g. labels, buttons, etc.).
	 * @since 0.4
	 *
	 * @return {jQuery[]}
	 */
	getElements: function() {
		return this._items;
	},

	/**
	 * This will add a toolbar element, e.g. a label or a button to the toolbar at the given index.
	 * @since 0.4
	 *
	 * @param {jQuery} $node Node featuring a toolbar item (e.g. a toolbar group, button or label).
	 * @param {Number} [index] Where to add the element (use negative values to specify the position
	 *        from the end).
	 */
	addElement: function( $node, index ) {
		// Check whether exact same element is displayed in toolbar already:
		var existingIndex = this.getIndexOf( $node );

		// Calculate actual index if negative index is given:
		if( index !== undefined && index < 0 ) {
			index = this.getLength() - index;
			index = index < 0 ? 0 : index;
		}

		if( existingIndex === index ) {
			return; // Displayed where it is supposed to be, do nothing.
		}

		if( existingIndex > -1 ) {
			// Displayed in some other place, remove and insert again in next step.
			this._items.splice( existingIndex, 1 );
		}

		if( index === undefined ) {
			// Add node as last one.
			this._items.push( $node );
		} else {
			// Add node at certain index.
			this._items.splice( index, 0, $node );
		}
		this.draw(); // TODO: could be more efficient when just adding one element
	},

	/**
	 * Removes an element from the toolbar.
	 * @since 0.4
	 *
	 * @param {jQuery} $node Node to remove
	 * @return {boolean} False if element isn't part of this element
	 */
	removeElement: function( $node ) {
		var index = this.getIndexOf( $node );
		if( index < 0 ) {
			return false;
		}
		this._items.splice( index, 1 );

		this.draw(); // TODO: could be more efficient when just removing one element
		return true;
	},

	/**
	 * Returns whether the given node is represented within the toolbar.
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	hasElement: function( $node ) {
		return this.getIndexOf( $node ) > -1;
	},

	/**
	 * Returns the index of a node within the toolbar, -1 in case the element is not present.
	 * @since 0.4
	 *
	 * @param {jQuery} $node
	 * @return {number}
	 */
	getIndexOf: function( $node ) {
		return $.inArray( $node, this._items );
	},

	/**
	 * Returns how many items are displayed in this toolbar.
	 * @since 0.4
	 *
	 * @return {number}
	 */
	getLength: function() {
		return this._items.length;
	},

	/**
	 * Hides the toolbar.
	 * @since 0.4
	 *
	 * @return {boolean} Whether toolbar is hidden
	 */
	hide: function() {
		if ( this._display === null || this._display === 'none' ) {
			this._display = this.element.css( 'display' );
		}
		this.element.css( 'display', 'none' );
		return this.isHidden();
	},

	/**
	 * Shows the toolbar.
	 * @since 0.4
	 *
	 * @return {boolean} Whether toolbar is visible
	 */
	show: function() {
		this.element.css( 'display', ( this._display === null ) ? 'block' : this._display );
		return !this.isHidden();
	},

	/**
	 * Determines whether this toolbar is hidden.
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	isHidden: function() {
		return ( this.element.css( 'display' ) === 'none' );
	},

	/**
	 * @see jQuery.Widget.disable
	 */
	disable: function() {
		return this._setState( 'disable' );
	},

	/**
	 * @see jQuery.Widget.enable
	 */
	enable: function() {
		return this._setState( 'enable' );
	},

	/**
	 * @param {string} state
	 */
	_setState: function( state ) {
		$.each( this._items, function( i, $item ) {
			$item.data( 'wikibase-toolbaritem' )[state]();
		} );
		return PARENT.prototype[state].call( this );
	}

} );

} )( wikibase, jQuery );
