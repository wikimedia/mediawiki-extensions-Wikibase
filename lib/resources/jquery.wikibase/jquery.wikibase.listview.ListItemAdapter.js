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
	 * @abstract
	 * @singleton
	 * @since 0.4
	 */
	var SELF = $.wikibase.listview.ListItemAdapter = function( listMemberWidget ) {
		if( !$.isFunction( listMemberWidget ) || !listMemberWidget.prototype.widgetName ) {
			throw new Error( 'For a new ListItemAdapter, a jQuery Widget constructor is required' );
		}
		this._listMemberWidget = listMemberWidget;
	};
	SELF.prototype = {
		/**
		 * The widget object used by list members
		 * @type jQuery.Widget
		 */
		_listMemberWidget: null,

		/**
		 * Returns the given string but prefixed with the used list members widget's event prefix.
		 *
		 * @since 0.4
		 *
		 * @param {String} [name]
		 * @return String
		 */
		prefixedEvent: function( name ) {
			return this._listMemberWidget.prototype.widgetEventPrefix + ( name || '' );
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
			return this._listMemberWidget.prototype.widgetBaseClass + ( name || '' );
		},

		liInstance: function( $node ) {
			return $node.data( this._listMemberWidget.prototype.widgetName ) || null;
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
		newListMember: function( $subject, value ) {
			return new this._listMemberWidget(
				// TODO: don't assume value can be set as option 'value'
				value !== undefined ? { 'value': value } : {},
				// give DOM element, otherwise .data() will be assigned to jQuery object and can't
				// be accessed via $.fn.data() which is checking for the data of the DOM element.
				$subject[0]
			);
		}
	};

}( mediaWiki, wikibase, jQuery ) );
