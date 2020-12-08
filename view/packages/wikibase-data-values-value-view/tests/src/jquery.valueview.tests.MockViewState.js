/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
jQuery.valueview.tests = jQuery.valueview.tests || {};

jQuery.valueview.tests.MockViewState = ( function( $, util ) {
	'use strict';

	/**
	 * Mock ViewState for usage in tests. Allows to inject the state as a plain object.
	 *
	 * @constructor
	 * @extends ViewState
	 * @since 0.1
	 *
	 * @param {Object} [definition={}] A plain object with the fields "isInEditMode", "isDisabled",
	 *        "value" and "options". This will just keep a reference to the object, so changing the
	 *        object from the outside will also update the ViewState's functions return values.
	 *
	 * @throws {Error} if definition is not a plain object.
	 */
	return util.inherit( 'ValueviewMockViewState', function ( definition ) {
		if ( definition !== undefined && !$.isPlainObject( definition ) ) {
			throw new Error( 'Given definition needs to be a plain object' );
		}
		this._view = definition || {};
	}, {
		/**
		 * @see ViewState.isInEditMode
		 */
		isInEditMode: function() {
			return !!this._view.isInEditMode;
		},

		/**
		 * @see ViewState.isDisabled
		 */
		isDisabled: function() {
			return !!this._view.isDisabled;
		},

		/**
		 * @see ViewState.value
		 */
		value: function() {
			return this._view.value;
		},

		/**
		 * @see ViewState.getFormattedValue
		 */
		getFormattedValue: function() {
			return this._view.getFormattedValue || '';
		},

		/**
		 * @see ViewState.getTextValue
		 */
		getTextValue: function() {
			return this._view.getTextValue || '';
		},

		/**
		 * @param key
		 * @see ViewState.option
		 */
		option: function( key ) {
			return ( this._view.options || {} )[ key ];
		}
	} );

}( jQuery, util ) );
