jQuery.valueview = jQuery.valueview || {};

( function( $, vv ) {
	'use strict';

	/**
	 * Allows to receive information about a related `valueview` object but doesn't provide
	 * functions to actively change the view. It serves as a state object to inform subsystems of
	 * the `valueview`'s current status. Those subsystems should not have full access to the entire
	 * view though since interaction in both directions would very likely mess things up.
	 * @class jQuery.valueview.ViewState
	 * @licence GNU GPL v2+
	 * @author Daniel Werner < daniel.werner@wikimedia.de >
	 * @since 0.1
	 *
	 * @constructor
	 *
	 * @param {jQuery.valueview} valueview
	 *
	 * @throws {Error} if no `jQuery.valueview` instance is provided.
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
		 * @property {jQuery.valueview}
		 * @private
		 */
		_view: null,

		/**
		 * @see jQuery.valueview.isInEditMode
		 * @inheritdoc jQuery.valueview#isInEditMode
		 */
		isInEditMode: function() {
			return this._view.isInEditMode();
		},

		/**
		 * Returns whether the related `valueview` is currently disabled.
		 * @return {boolean}
		 */
		isDisabled: function() {
			return this._view.option( 'disabled' );
		},

		/**
		 * Returns the related `valueview`'s current value. Does not allow to change the value.
		 * @see jQuery.valueview.value
		 * @inheritdoc jQuery.valueview#value
		 */
		value: function() {
			return this._view.value();
		},

		/**
		 * Returns the related `valueview`'s current formatted value.
		 * @see jQuery.valueview.getFormattedValue
		 * @inheritdoc jQuery.valueview#getFormattedValue
		 */
		getFormattedValue: function() {
			return this._view.getFormattedValue();
		},

		/**
		 * Returns the related `valueview`'s current plain text value.
		 * @see jQuery.valueview.getTextValue
		 * @inheritdoc jQuery.valueview#getTextValue
		 * @since 0.4
		 */
		getTextValue: function() {
			return this._view.getTextValue();
		},

		/**
		 * Returns the options or a specific option of the related `valueview`. Does not allow
		 * to set any option.
		 * @see jQuery.Widget.option
		 *
		 * @param {string} [key]
		 * @return {*}
		 */
		option: function( key ) {
			return this._view.option( key );
		}
	} );

}( jQuery, jQuery.valueview ) );
