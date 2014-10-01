/**
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $ ) {
	'use strict';

	/**
	 * Required as information about the kind of widget used for items in a listview widget.
	 *
	 * ARCHITECTURAL NOTE: This is basically a strategy giving the listview information about what
	 *  kinds of items it deals with. An alternative attempt would be for the listview to use
	 *  composition where the list would own one object for each of its list items, giving the list
	 *  information about that item. This attempt would allow to handle values of different kinds
	 *  in the same listview widget but would require an additional object per list item while our
	 *  current attempt only requires one strategy object which can be shared by all lists dealing
	 *  with that value type. Allowing the listview to deal with different kinds of values but
	 *  sticking with this pattern might also be possible by providing the listview with a function
	 *  to determine the kind of a value and then choosing the right strategy to deal with the value.
	 *
	 * @param {Object} options See each particular options documentation. Each option marked as
	 *        '(required)' has to be given as a field within the object.
	 *
	 * @option listItemWidget {Function} (required) The constructor of the jQuery widget which
	 *         should represent items in a list using this ListItemAdapter definition.
	 *
	 * @option newItemOptionsFn {Function} (required) A callback which will be called when the
	 *         related list is instantiating a new list item. The callback has to return an Object
	 *         which will then be used as options object for a new widget (specified in
	 *         'listItemWidget' option) representing the new list item's value. The new new list
	 *         item's value is given as the callback's first parameter, if an empty list item should
	 *         be created, the value will be null. The callback's context is the ListItemAdapter
	 *         instance.
	 *
	 * @option listItemWidgetValueAccessor {string} (required) The name of the function which acts
	 *         as setter/getter for the value on the listItemWidget.
	 *
	 * @since 0.4
	 */
	var SELF = $.wikibase.listview.ListItemAdapter = function( options ) {
		this._liWidget = options.listItemWidget;

		if( !$.isFunction( this._liWidget ) || !this._liWidget.prototype.widgetName ) {
			throw new Error( 'For a new ListItemAdapter, a jQuery Widget constructor is required' );
		}
		if( !$.isFunction( options.newItemOptionsFn ) ) {
			throw new Error( 'For a new ListItemAdapter, the \'newItemOptionsFn\' option is required' );
		}
		if( typeof options.listItemWidgetValueAccessor !== 'string'
			|| this._liWidget.prototype[ options.listItemWidgetValueAccessor ] === undefined
		) {
			throw new Error( 'The \'listItemWidgetValueAccessor\' option has to be the name of the' +
				'list item widget\'s function to set and get a value' );
		}

		this._options = options;
	};
	$.extend( SELF.prototype, {
		/**
		 * The options object given in the constructor.
		 * @type Object
		 */
		_options: null,

		/**
		 * The widget constructor used for new list members.
		 * Short-cut to this._options.listItemWidget
		 * @type Function
		 */
		_liWidget: null,

		/**
		 * Returns the given string but prefixed with the used list members widget's event prefix.
		 *
		 * @since 0.4
		 *
		 * @param {String} [name]
		 * @return String
		 */
		prefixedEvent: function( name ) {
			return this._liWidget.prototype.widgetEventPrefix + ( name || '' );
		},

		/**
		 * Returns the given string but prefixed with the used list member's base class.
		 *
		 * @since 0.4
		 *
		 * @param {String} [name]
		 * @return String
		 */
		prefixedClass: function( name ) {
			return this._liWidget.prototype.widgetFullName + ( name || '' );
		},

		liInstance: function( $node ) {
			return $node.data( this._liWidget.prototype.widgetName ) || null;
		},

		/**
		 * Allows to get a value from a list item or to set a value on a list item.
		 *
		 * @since 0.4
		 *
		 * @param $listItem
		 * @param [value] if provided, this will be set as the list item's new value.
		 */
		liValue: function( $listItem, value ) {
			var li = this.liInstance( $listItem );

			if( !li ) {
				throw new Error( 'A proper list item must be provided' );
			}
			return li[ this._options.listItemWidgetValueAccessor ]( value );
		},

		/**
		 * Returns options suitable for a new widget representing a value as list item within the
		 * ListItemAdapter's related list.
		 *
		 * @since 0.4
		 *
		 * @param {*} value
		 * @return Object
		 */
		newListItemOptions: function( value ) {
			// if the value is undefined, this is the same as null, which means empty value
			value = value === undefined ? null : value;
			return this._options.newItemOptionsFn.call( this, value );
		},

		/**
		 * Returns a new list item. If the value parameter is omitted or null, an empty list item
		 * which can be displayed for the user to insert a new value will be returned.
		 *
		 * @since 0.4
		 *
		 * @param {jQuery} $subject The DOM node the widget will be initialized on
		 * @param {*} [value] Value of the new list member. If this is null or undefined, the new
		 *        list member will be an empty one, ready for the user to enter some value.
		 * @return jQuery.Widget
		 */
		newListItem: function( $subject, value ) {
			return new this._liWidget(
				this.newListItemOptions( value ),
				// give DOM element, otherwise .data() will be assigned to jQuery object and can't
				// be accessed via $.fn.data() which is checking for the data of the DOM element.
				$subject[0]
			);
		}
	} );

}( jQuery ) );
