/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

	/**
	 * Required as information about the kind of widget used for items in a listview widget.
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
	 *         be created, the value will be undefined. The callback's context is the
	 *         ListItemAdapter instance.
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

		this._options = options;
	};
	SELF.prototype = {
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
			return this._liWidget.prototype.widgetBaseClass + ( name || '' );
		},

		liInstance: function( $node ) {
			return $node.data( this._liWidget.prototype.widgetName ) || null;
		},

		/**
		 * Returns options suitable for a new widget representing a value as list item within the
		 * ListItemAdapter's related list.
		 *
		 * @param {*} value
		 * @return Object
		 */
		newListItemOptions: function( value ) {
			return this._options.newItemOptionsFn.call( this, value );
		},

		/**
		 * Returns a new list item. If the value parameter is omitted or null, an empty list item
		 * which can be displayed for the user to insert a new value will be returned.
		 *
		 * @since 0.4
		 *
		 * @param {jQuery} subject The DOM node the widget will be initialized on
		 * @param {*} [value] Value of the new list member
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
	};

}( mediaWiki, wikibase, jQuery ) );
