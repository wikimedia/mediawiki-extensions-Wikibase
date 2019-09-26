( function () {
	'use strict';

	require( './jquery.wikibase.listview.js' );

	/**
	 * Interface between a `jQuery.wikibase.listview` instance and the widget the `listview` is
	 * supposed to use for its list items.
	 * ARCHITECTURAL NOTE: This, basically, is a strategy providing information to the `listview`
	 *  about the kind of items it deals with. An alternative would be to use composition with the
	 *  `listview` managing a dedicated object for each list item. While that would allow handling
	 *  values of different kinds in the same `listview` widget, it would require an additional
	 *  object per list item. By using a single `ListItemAdapter`, only one object is required as it
	 *  can be applied to all `listview` items. Enabling the `listview` to deal with different kinds
	 *  of values but sticking with this pattern might be possible by providing a function the
	 *  `listview` determining the kind of a value and choosing the right strategy to deal with the
	 *  value accordingly.
	 *
	 * @see jQuery.wikibase.listview
	 * @class jQuery.wikibase.listview.ListItemAdapter
	 * @license GPL-2.0-or-later
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {Function} options.listItemWidget
	 *        The constructor of the jQuery widget which should represent items in a `listview`.
	 *        The widget is required to feature `value`, `destroy` and `option` methods.
	 * @param {Function} [options.newItemOptionsFn]
	 *        A function called when the related `listview` is instantiating a new list item. The
	 *        function has to return an `Object` which will then be used as `options` object for a
	 *        new widget (which is specified in the `listItemWidget` option).
	 *        The new list item's value is given as the function's first parameter, if an empty
	 *        list item should be created, the value will be `null`. The function's context is the
	 *        `ListItemAdapter` instance.
	 *        Either the `newItemOptionsFn` or the `getNewItem` option has to be passed.
	 * @param {Function} [options.getNewItem]
	 *        A function called when the related `listview` is instantiating a new list item. The
	 *        function has to return an instance of `options.listItemWidget`.
	 *        The new list item's value is given as the function's first parameter, if an empty
	 *        list item should be created, the value will be `null`. The function's context is the
	 *        `ListItemAdapter` instance. The second parameter is the DOM element the list item widget
	 *        should be initialized on.
	 *        Either the `newItemOptionsFn` or the `getNewItem` option has to be passed.
	 *
	 * @throws {Error} if a required option is not specified properly.
	 * @throws {Error} if the widget specified in the `listItemWidget` option does not feature a
	 *         `value` method.
	 */
	var SELF = $.wikibase.listview.ListItemAdapter = function WbListviewListItemAdapter( options ) {
		if ( typeof options.listItemWidget !== 'function'
			|| !options.listItemWidget.prototype.widgetName
			|| !options.listItemWidget.prototype.widgetEventPrefix
		) {
			throw new Error( 'For a new ListItemAdapter, a jQuery Widget constructor is required' );
		}
		if ( typeof options.listItemWidget.prototype.value !== 'function' ) {
			throw new Error(
				'For a new ListItemAdapter, the list item prototype needs a "value" method'
			);
		}
		if ( typeof options.listItemWidget.prototype.destroy !== 'function' ||
			typeof options.listItemWidget.prototype.option !== 'function'
		) {
			mw.log.warn(
				'For a new ListItemAdapter, the list item prototype needs "destroy" and "option" methods'
			);
		}
		if ( typeof options.newItemOptionsFn !== 'function' && typeof options.getNewItem !== 'function' ) {
			throw new Error(
				'For a new ListItemAdapter, the "newItemOptionsFn" or the "getNewItem" option has to be passed'
			);
		}

		if ( !options.getNewItem ) {
			var self = this;
			options.getNewItem = function ( value, subjectDom ) {
				return new options.listItemWidget(
					options.newItemOptionsFn.call( self, value === undefined ? null : value ),
					subjectDom
				);
			};
		}

		this._options = options;
	};
	$.extend( SELF.prototype, {
		/**
		 * @property {Object}
		 * @protected
		 */
		_options: null,

		/**
		 * Returns the given string but prefixed with the list item widget's event prefix.
		 *
		 * @param {string} [name]
		 * @return {string}
		 */
		prefixedEvent: function ( name ) {
			return this._options.listItemWidget.prototype.widgetEventPrefix + ( name || '' );
		},

		/**
		 * Returns the list item widget instance initialized on the (list item) node provided.
		 *
		 * @param {jQuery} $node
		 * @return {*|null}
		 */
		liInstance: function ( $node ) {
			return $node.data( this._options.listItemWidget.prototype.widgetName ) || null;
		},

		/**
		 * Returns a new list item. If the `value` parameter is omitted or `null`, an empty list
		 * item which can be displayed for the user to insert a new value will be returned.
		 *
		 * @param {jQuery} $subject The DOM node the widget will be initialized on.
		 * @param {*} [value] Value of the new list item. If `null` or `undefined`, the new
		 *        list item will be an empty one.
		 * @return {jQuery.Widget}
		 */
		newListItem: function ( $subject, value ) {
			var item = this._options.getNewItem( value, $subject[ 0 ] );
			if ( !( item instanceof $.Widget ) ) {
				throw new Error( 'The "getNewItem" option must return a jQuery.Widget' );
			}
			return item;
		}
	} );

}() );
