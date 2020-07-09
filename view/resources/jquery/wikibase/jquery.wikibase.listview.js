( function () {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

	/**
	 * View for displaying and editing list items, each represented by a single random widget.
	 *
	 * @class jQuery.wikibase.listview
	 * @extends jQuery.ui.TemplatedWidget
	 * @license GPL-2.0-or-later
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {*[]} [options.value=null]
	 *        The values displayed by this view. More specifically, a list of each list item widget's
	 *        value.
	 * @param {jQuery.wikibase.listview.ListItemAdapter} options.listItemAdapter
	 *        Interfaces the actual widget instances to be used by the `listview`. Cannot be changed
	 *        after initialization.
	 * @param {string} [options.listItemNodeName='DIV']
	 *         Node name of the base node of new list items.
	 */
	$.widget( 'wikibase.listview', PARENT, {
		/**
		 * @inheritdoc
		 * @protected
		 */
		options: {
			template: 'wikibase-listview',
			templateParams: [
				'' // list items
			],
			value: null,
			listItemAdapter: null,
			listItemNodeName: 'DIV'
		},

		/**
		 * Short-cut for `this.options.listItemAdapter`.
		 *
		 * @property {jQuery.wikibase.listview.ListItemAdapter}
		 * @private
		 */
		_lia: null,

		/**
		 * The DOM elements this `listview`'s element contained when it was initialized. These DOM
		 * elements are reused in `this._addLiValue` until the array is empty.
		 *
		 * @property {HTMLElement[]|null}
		 * @private
		 */
		_reusedItems: null,

		/**
		 * @inheritdoc
		 * @protected
		 *
		 * @throws {Error} if a required option is not specified properly.
		 */
		_create: function () {
			this._lia = this.options.listItemAdapter;

			if ( typeof this._lia !== 'object'
				|| !( this._lia instanceof $.wikibase.listview.ListItemAdapter )
			) {
				throw new Error( 'Option "listItemAdapter" has to be an instance of '
					+ 'jQuery.wikibase.listview.ListItemAdapter' );
			}

			this._reusedItems = $.makeArray( this.element.children( this.options.listItemNodeName ) );

			PARENT.prototype._create.call( this );

			this._createList();
		},

		/**
		 * @inheritdoc
		 */
		destroy: function () {
			var self = this;
			this.items().each( function () {
				self._removeItem( $( this ) );
			} );
			this._lia = null;
			this._reusedItems = null;
			PARENT.prototype.destroy.call( this );
		},

		/**
		 * @inheritdoc
		 * @protected
		 *
		 * @throws {Error} when trying to set `listItemAdapter` option.
		 */
		_setOption: function ( key, value ) {
			var self = this;

			if ( key === 'listItemAdapter' ) {
				throw new Error( 'Can not change the ListItemAdapter after initialization' );
			} else if ( key === 'value' ) {
				this.items().each( function () {
					self._removeItem( $( this ) );
				} );

				for ( var i = 0; i < value.length; i++ ) {
					this._addLiValue( value[ i ] );
				}
			}

			var response = PARENT.prototype._setOption.apply( this, arguments );

			if ( key === 'disabled' ) {
				this.items().each( function () {
					var liInstance = self._lia.liInstance( $( this ) );
					// Check if instance got destroyed in the meantime:
					if ( liInstance ) {
						liInstance.option( key, value );
					}
				} );
			}

			return response;
		},

		/**
		 * Fills the list element with DOM structure for each list item.
		 *
		 * @private
		 */
		_createList: function () {
			var i, items = this.option( 'value' );

			if ( items === null ) {
				for ( i = this._reusedItems.length; i--; ) {
					this._addLiValue( null );
				}
			} else {
				for ( i in items ) {
					this._addLiValue( items[ i ] );
				}
			}
		},

		/**
		 * Sets the widget's value or gets the widget's current value. The widget's non-pending value
		 * (the value the widget was initialized with) may be retrieved via `this.option( 'value' )`.
		 *
		 * @param {*[]} [value] List containing a value for each list item widget.
		 * @return {*[]|undefined}
		 */
		value: function ( value ) {
			if ( value !== undefined ) {
				return this.option( 'value', value );
			}

			var self = this,
				values = [];

			this.items().each( function () {
				values.push( self._lia.liInstance( $( this ) ) );
			} );

			return values;
		},

		/**
		 * Returns all list item nodes. The `listItemAdapter` may be used to retrieve the list item
		 * instance.
		 *
		 * @return {jQuery}
		 */
		items: function () {
			return this.element.children( '.' + this.widgetName + '-item' );
		},

		/**
		 * Returns all list items which have a value not considered empty (not `null`).
		 *
		 * @return {jQuery}
		 */
		nonEmptyItems: function () {
			var lia = this._lia;
			return this.items().filter( function () {
				var item = lia.liInstance( $( this ) );
				return !!item.value();
			} );
		},

		/**
		 * Returns the index of a given item node within the list managed by the `listview`. Returns
		 * `-1` if the node could not be found.
		 *
		 * @param {jQuery} $itemNode
		 * @return {number}
		 */
		indexOf: function ( $itemNode ) {
			var $items = this.items(),
				itemNode = $itemNode.get( 0 );

			for ( var i = 0; i < $items.length; i++ ) {
				if ( $items.get( i ) === itemNode ) {
					return i;
				}
			}

			return -1;
		},

		/**
		 * Returns the list item adapter object interfacing to this list's list items.
		 *
		 * @return {jQuery.wikibase.listview.ListItemAdapter}
		 */
		listItemAdapter: function () {
			return this._lia;
		},

		/**
		 * Adds one list item into the list and renders it in the view.
		 *
		 * @param {*} liValue One list item widget's value.
		 * @return {jQuery} New list item's node.
		 */
		addItem: function ( liValue ) {
			return this._addLiValue( liValue );
		},

		/**
		 * Adds one list item into the list and renders it in the view.
		 *
		 * @private
		 *
		 * @param {*} liValue One list item widget's value.
		 * @return {jQuery} New list item's node.
		 */
		_addLiValue: function ( liValue ) {
			var $newLi = this._reusedItems.length > 0
				? $( this._reusedItems.shift() )
				: $( '<' + this.option( 'listItemNodeName' ) + '/>' );

			$newLi.addClass( this.widgetName + '-item' );

			if ( !$newLi.parent( this.element ).length ) {
				// Insert DOM first, to allow events bubbling up the DOM tree.
				var items = this.items();

				if ( items.length ) {
					items.last().after( $newLi );
				} else {
					this.element.append( $newLi );
				}
			}

			this._lia.newListItem( $newLi, liValue );

			return $newLi;
		},

		/**
		 * Removes one list item from the list and renders the update in the view.
		 *
		 * @param {jQuery} $li The list item's node to be removed.
		 *
		 * @throws {Error} if the node provided is not a list item.
		 */
		removeItem: function ( $li ) {
			if ( !$li.parent( this.element ).length ) {
				throw new Error( 'The given node is not an element in this list' );
			}

			this._removeItem( $li );

			// FIXME: Remove all itemremoved events, see https://gerrit.wikimedia.org/r/298766.
			this._trigger( 'itemremoved', null, [ null, $li ] );
		},

		_removeItem: function ( $li ) {
			this._lia.liInstance( $li ).destroy();
			$li.remove();
		},

		/**
		 * Inserts a new list item into the list. The new list item will be a widget instance of the
		 * type set on the list, but without any value.
		 *
		 * @return {Object} jQuery.Promise
		 * @return {Function} return.done
		 * @return {jQuery} return.done.$newLi The new list item node. Use
		 *         `listItemAdapter().liInstance( $newLi )` to receive the widget instance.
		 */
		enterNewItem: function () {
			var $newLi = this._addLiValue();
			return $.Deferred().resolve( $newLi ).promise();
		},

		/**
		 * @inheritdoc
		 */
		focus: function () {
			var $items = this.items();

			if ( $items.length ) {
				var item = this._lia.liInstance( $items.first() );
				if ( item.focus ) {
					item.focus();
					return;
				}
			}

			this.element.trigger( 'focus' );
		},

		/**
		 * Starts the list item's edit modes.
		 *
		 * @return {Object} jQuery.Promise
		 *         No resolved parameters.
		 *         Rejected parameters:
		 *         - {Error}
		 */
		startEditing: function () {
			return $.when.apply( $, this.value().map( function ( listitem ) {
				return listitem.startEditing();
			} ) );
		},

		/**
		 * Stops the list item's edit modes.
		 *
		 * @param {boolean} dropValue
		 * @return {Object} jQuery.Promise
		 *         No resolved parameters.
		 *         Rejected parameters:
		 *         - {Error}
		 */
		stopEditing: function ( dropValue ) {
			return $.when.apply( $, this.value().map( function ( listitem ) {
				return listitem.stopEditing( dropValue );
			} ) );
		}

	} );

}() );
