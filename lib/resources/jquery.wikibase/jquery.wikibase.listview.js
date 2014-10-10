/**
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $ ) {
	'use strict';

	var PARENT =  $.TemplatedWidget;

/**
 * View for displaying and editing several list items, each represented by another widget.
 * @since 0.4
 *
 * @option {*|null} value The values displayed by this view. Each value is represented by a widget
 *         defined in the 'listItemAdapter' option.
 *
 * @option {jQuery.wikibase.listview.ListItemAdapter} listItemAdapter (required) Can not
 *         be changed after initialization.
 *
 * @option {string} [listItemNodeName]
 *         Node name of the base node of new list items.
 *         Default: 'DIV'
 *
 * @event additem: Triggered before a list item will be added to the list.
 *        (1) {jQuery.Event}
 *        (2) {*|null} The value the new list item will represent. This can also be null in case a
 *            new, empty list item, not yet representing any value but ready for the user to enter
 *            a value, will be added.
 *        (3) {jQuery} the DOM node on which a widget representing the new list item's value will
 *            be initialized. The widget will be initialized on this DOM node after the DOM node is
 *            appended to the list, so events can bubble during widget initialization.
 *
 * @event itemadded: Triggered after a list item got added to the list.
 *        (1) {jQuery.Event}
 *        (2) {*|null} The value the new list item is representing. null for empty value.
 *        (3) {jQuery} The DOM node with the widget, representing the value.
 *
 * @event removeitem: Triggered before a list item will be removed from the list.
 *        (1) {jQuery.Event}
 *        (2) {*|null} The value of the list item which will be removed. null for empty value.
 *        (3) {jQuery} The list item's DOM node, which will be removed.
 *
 * @event itemremoved: Triggered after a list got removed from the list.
 *        (1) {jQuery.Event}
 *        (2) {*|null} The value of the list item which will be removed. null for empty value.
 *        (3) {jQuery} The list item's DOM node, removed.
 *
 * @event enternewitem: Triggered when initializing the process of adding a new item to the list.
 *        (1) {jQuery.Event}
 *        (2) {jQuery} The DOM node pending to be added permanently to the list.
 *
 * @event afteritemmove: Triggered when an item node is moved within the list.
 *        (1) {jQuery.Event}
 *        (2) {number} The item node's new index.
 *        (3) {number} Number of items in the list.
 */
$.widget( 'wikibase.listview', PARENT, {
	/**
	 * Short cut for 'listItemAdapter' option
	 * @type jQuery.wikibase.listview.ListItemAdapter
	 */
	_lia: null,

	/**
	 * The DOM elements this ListView's element contained when it was initialized.
	 * These DOM elements are reused in addItem until the array is empty.
	 *
	 * @type [HTMLElement]
	 */
	_reusedItems: [],

	/**
	 * (Additional) default options
	 * @see jQuery.Widget.options
	 */
	options: {
		template: 'wb-listview',
		templateParams: [
			'' // list items
		],
		value: null,
		listItemAdapter: null,
		listItemNodeName: 'DIV'
	},

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		this._lia = this.options.listItemAdapter; // create short-cut for this

		if( typeof this._lia !== 'object'
			|| !( this._lia instanceof $.wikibase.listview.ListItemAdapter )
		) {
			throw new Error( "Option 'listItemAdapter' has to be an instance of $.wikibase." +
				"listview.ListItemAdapter" );
		}

		this._reusedItems = $.makeArray( this.element.children( this.options.listItemNodeName ) );

		// apply template to this.element:
		PARENT.prototype._create.call( this );

		this._createList(); // fill list with items
	},

	/**
	 * @see jQuery.TemplatedWidget.destroy
	 */
	destroy: function() {
		this._lia = null;
		this._reusedItems = null;
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @see jQuery.widget._setOption
	 * We are using this to disallow changing the 'listItemAdapter' option afterwards
	 */
	_setOption: function( key, value ) {
		var self = this;

		if( key === 'listItemAdapter' ) {
			throw new Error( 'Can not change the ListItemAdapter after initialization' );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this.items().each( function() {
				var liInstance = self._lia.liInstance( $( this ) );
				// Check if instance got destroyed in the meantime:
				if( liInstance ) {
					liInstance.option( key, value );
				}
			} );
		}

		return response;
	},

	/**
	 * Will fill the list element with sections DOM, all sections will already contain their related
	 * list items DOM.
	 *
	 * @since 0.4
	 */
	_createList: function() {
		var i, items = this.option( 'value' );

		// initialize view for each of the list item values:
		for( i in items ) {
			this.addItem( items[i] );
		}
	},

	/**
	 * Sets/gets the listview's list item instances.
	 *
	 * @param {*[]} [value]
	 * @return {*[]|undefined}
	 */
	value: function( value ) {
		var self = this;

		// Getter:
		if( value === undefined ) {
			var values = [];

			this.items().each( function( i, node ) {
				values.push( self._lia.liInstance( $( node ) ) );
			} );

			return values;
		}

		// Clear listview:
		this.items().each( function( i, node ) {
			var $node = $( node );
			self._lia.liInstance( $node ).destroy();
			$node.remove();
		} );

		// Add new values:
		for( var i = 0; i < value.length; i++ ) {
			var $newLi = $( '<' + this.option( 'listItemNodeName' ) +'/>' )
				.addClass( this.widgetName + '-item' );
			this.element.append( $newLi );
			this._lia.newListItem( $newLi, value[i] );
		}
	},

	/**
	 * Returns all list item nodes.
	 *
	 * @since 0.4
	 *
	 * @return {jQuery}
	 */
	items: function() {
		return this.element.children( '.' + this.widgetName + '-item' );
	},

	/**
	 * Returns all list items which have a value not considered empty (not null).
	 *
	 * @since 0.4
	 *
	 * @return {jQuery}
	 */
	nonEmptyItems: function() {
		var lia = this._lia;
		return this.items().filter( function( i ) {
			return !!lia.liValue( $( this ) );
		} );
	},

	/**
	 * Returns the index of a given item node within the list managed by the listview. Returns -1 if
	 * the node could not be found.
	 * @since 0.4
	 *
	 * @param {jQuery} $itemNode
	 * @return {number}
	 */
	indexOf: function( $itemNode ) {
		var $items = this.items(),
			itemNode = $itemNode.get( 0 );

		for( var i = 0; i < $items.length; i++ ) {
			if( $items.get( i ) === itemNode ) {
				return i;
			}
		}

		return -1;
	},

	/**
	 * Moves a list item to a new index.
	 * @since 0.4
	 *
	 * @param {jQuery} $itemNode
	 * @param {number} toIndex
	 *
	 * @triggers afteritemmove
	 */
	move: function( $itemNode, toIndex ) {
		var currIndex = this.indexOf( $itemNode ),
			items = this.items();

		// No need to move if the item has the index already or if it should be moved to after the
		// last item although it is at the end already:
		if(
			currIndex < 0
			|| currIndex === toIndex
			|| currIndex === items.length - 1 && toIndex >= items.length
		) {
			return;
		}

		if( toIndex >= items.length ) {
			$itemNode.insertAfter( items.last() );
		} else if( items.eq( toIndex ).prev().get( 0 ) === $itemNode.get( 0 ) ) {
			// Item already is at the position it shall be moved to.
			return;
		} else {
			$itemNode.insertBefore( items.eq( toIndex ) );
		}

		this._trigger( 'afteritemmove', null, [ this.indexOf( $itemNode ), items.length ] );
	},

	/**
	 * Moves an item node one index towards the top of the list.
	 * @since 0.4
	 *
	 * @param {jQuery} $itemNode
	 */
	moveUp: function( $itemNode ) {
		if( this.indexOf( $itemNode ) !== 0 ) {
			this.move( $itemNode, this.indexOf( $itemNode ) - 1 );
		}
	},

	/**
	 * Moves an item node one index towards the bottom of the list.
	 * @since 0.4
	 *
	 * @param {jQuery} $itemNode
	 */
	moveDown: function( $itemNode ) {
		// Adding 2 to the index to move the element to before the element after the next element:
		this.move( $itemNode, this.indexOf( $itemNode ) + 2 );
	},

	/**
	 * Returns the list item adapter object to deal with this list's list items.
	 * @return {jQuery.wikibase.listview.ListItemAdapter}
	 */
	listItemAdapter: function() {
		return this._lia;
	},

	/**
	 * Adds one list item into the list and renders it in the view.
	 * @since 0.4
	 *
	 * @triggers additem
	 * @triggers itemadded If default was not prevented by 'additem' event.
	 *
	 * @param {*} value
	 * @return {jQuery} The DOM node representing the value. If default was prevented in the
	 *         'additem' event, the node will be returned even though not appended to the list.
	 */
	addItem: $.NativeEventHandler( 'additem', {
		initially: function( event, value ) {
			// in custom handlers, we provide the DOM node without initialized value widget because
			// we want to initialize widget AFTER the node is in the DOM, so we can have events
			// triggered during widget initialization bubble up the DOM!
			var $newLi;
			if( this._reusedItems.length > 0 ) {
				$newLi = $( this._reusedItems.shift() );
			} else {
				$newLi = $( '<' + this.option( 'listItemNodeName' ) + '/>' );
			}
			$newLi.addClass( this.widgetName + '-item' );
			event.handlerArgs = [ value || null, $newLi ];
			return $newLi;
		},
		natively: function( event, value, $newLi ) {
			if( !$newLi.parent( this.element ).length ) {
				// first insert DOM so value widget's events can already bubble during initialization!
				var items = this.items();

				if( items.length ) {
					items.last().after( $newLi );
				} else {
					this.element.append( $newLi );
				}
			}

			this._lia.newListItem( $newLi, value );

			this._trigger( 'itemadded', null, [ value, $newLi ] );
		}
	} ),

	/**
	 * Removes one list item from the list and renders the update in the view.
	 * @since 0.4
	 *
	 * @triggers removeitem
	 * @triggers itemremoved If default was not prevented by 'removeitem' event.
	 *
	 * @param {jQuery} $itemNode The list item's node to be removed
	 */
	removeItem: $.NativeEventHandler( 'removeitem', {
		initially: function( event, $itemNode ) {
			// check whether given node actually is in this list. If not, fail!
			if( !$itemNode.parent( this.element ).length ) {
				throw new Error( 'The given node is not an element in this list' );
			}
			// even though this information is kind of redundant since the value can be accessed
			// within custom events by using listview.listItemAdapter().liValue( $itemNode), we
			// provide the value here for convenience and for consistent event argument order in all
			// add/remove events
			var value = this._lia.liValue( $itemNode );
			event.handlerArgs = [ value, $itemNode ];
		},
		natively: function( event, value, $itemNode ) {
			// destroy widget representing the list item's value and remove node from list:
			this._lia.liInstance( $itemNode ).destroy();

			$itemNode.remove();

			// For correctly counting the listview items (e.g. for the references), the
			// "itemremoved" event has to be triggered after the item node got removed to not count
			// a pending list item that is about to be removed.
			this._trigger( 'itemremoved', null, [ value, $itemNode ] );
		}
	} ),

	/**
	 * Will insert a new list member into the list. The new list member will be a Widget of the type
	 * displayed in the list, but without value, so the user can specify a value.
	 *
	 * @since 0.4
	 */
	enterNewItem: function() {
		var $newLi = this.addItem();
		this._trigger( 'enternewitem', null, [ $newLi ] );
	}

} );

// We have to override this here because $.widget sets it no matter what's in
// the prototype
$.wikibase.listview.prototype.widgetBaseClass = 'wb-listview';

}( jQuery ) );
