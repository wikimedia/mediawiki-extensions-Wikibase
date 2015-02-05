( function( $ ) {
	'use strict';

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
	 * @see jQuery.wikibase.listview
	 * @class jQuery.wikibase.listview.ListItemAdapter
	 * @since 0.4
	 * @licence GNU GPL v2+
	 * @author Daniel Werner < daniel.werner@wikimedia.de >
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {Function} options.listItemWidget
	 *        The constructor of the jQuery widget which should represent items in a `listview`.
	 *        The widget is required to feature a `value` method that allows setting and retrieving
	 *        the widget's value.
	 * @param {Function} options.newItemOptionsFn
	 *        A function called when the related `listview` is instantiating a new list item. The
	 *        function has to return an `Object` which will then be used as `options` object for a
	 *        new widget (which is specified in the `listItemWidget` option).
	 *        The new new list item's value is given as the function's first parameter, if an empty
	 *        list item should be created, the value will be `null`. The function's context is the
	 *        `ListItemAdapter` instance.
	 *
	 * @throws {Error} if a required option is not specified properly.
	 * @throws {Error} if the widget specified in the `listItemWidget` option does not feature a
	 *         `value` function.
	 */
	var SELF = $.wikibase.listview.ListItemAdapter = function( options ) {
		if(
			!$.isFunction( options.listItemWidget )
			|| !options.listItemWidget.prototype.widgetName
		) {
			throw new Error( 'For a new ListItemAdapter, a jQuery Widget constructor is required' );
		}
		if( !$.isFunction( options.listItemWidget.prototype.value ) ) {
			throw new Error(
				'For a new ListItemAdapter, the list item prototype needs a "value" method'
			);
		}
		if( !$.isFunction( options.newItemOptionsFn ) ) {
			throw new Error(
				'For a new ListItemAdapter, the "newItemOptionsFn" option is required'
			);
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
		prefixedEvent: function( name ) {
			return this._options.listItemWidget.prototype.widgetEventPrefix + ( name || '' );
		},

		/**
		 * Returns the list item widget instance initialized on the (list item) node provided.
		 *
		 * @param {jQuery} $node
		 * @return {*|null}
		 */
		liInstance: function( $node ) {
			return $node.data( this._options.listItemWidget.prototype.widgetName ) || null;
		},

		/**
		 * Returns the options suitable for a new list item widget.
		 *
		 * @param {*} value
		 * @return {Object}
		 */
		newListItemOptions: function( value ) {
			// if the value is undefined, this is the same as null, which means empty value
			value = value === undefined ? null : value;
			return this._options.newItemOptionsFn.call( this, value );
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
		newListItem: function( $subject, value ) {
			return new this._options.listItemWidget(
				this.newListItemOptions( value ),
				// give DOM element, otherwise .data() will be assigned to jQuery object and can't
				// be accessed via $.fn.data() which is checking for the data of the DOM element.
				$subject[0]
			);
		}
	} );

}( jQuery ) );
