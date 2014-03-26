/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
jQuery.valueview = jQuery.valueview || {};

( function( $, vv ) {
	'use strict';

	/**
	 * Allows to receive information about a related valueview object but doesn't provide functions
	 * to actively change the view. It serves as a state object to inform subsystems of the view's
	 * current status. Those subsystems should not have full access to the entire view though since
	 * interaction in both directions would very likely mess things up.
	 *
	 * @constructor
	 * @since 0.1
	 *
	 * @param {jQuery.valueview} valueview
	 */
	var SELF = vv.ViewState = function ValueviewViewState( valueview ) {
		if( !( valueview instanceof vv.valueview ) ) {
			throw new Error( 'Can not create a valueview ViewState object without a valueview' );
		}
		this._view = valueview;
	};

	$.extend( SELF.prototype, {
		/**
		 * The widget object whose status is represented.
		 * @type jQuery.valueview
		 */
		_view: null,

		/**
		 * @see jQuery.valueview.isInEditMode
		 *
		 * @since 0.1
		 */
		isInEditMode: function() {
			return this._view.isInEditMode();
		},

		/**
		 * @see jQuery.valueview.isDisabled
		 *
		 * @since 0.1
		 */
		isDisabled: function() {
			return this._view.isDisabled();
		},

		/**
		 * Returns the related view's current value. Does not allow to change the value.
		 * @see jQuery.valueview.value
		 *
		 * @since 0.1
		 */
		value: function() {
			return this._view.value();
		},

		/**
		 * Returns the related valueview's current formatted value.
		 * @see jQuery.valueview.getFormattedValue
		 * @since 0.1
		 */
		getFormattedValue: function() {
			return this._view.getFormattedValue();
		},

		/**
		 * Returns the related valueview's current plain text value.
		 * @see jQuery.valueview.getTextValue
		 * @since 0.4
		 */
		getTextValue: function() {
			return this._view.getTextValue();
		},

		/**
		 * Returns the options or a specific option of the related view. Does not allow to set any
		 * option.
		 * @see jQuery.Widget.option
		 *
		 * @since 0.1
		 *
		 * @param key
		 * @returns {*}
		 */
		option: function( key ) {
			return this._view.option( key );
		}
	} );

}( jQuery, jQuery.valueview ) );
