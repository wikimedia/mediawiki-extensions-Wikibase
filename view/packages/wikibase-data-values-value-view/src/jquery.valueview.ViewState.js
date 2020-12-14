$.valueview = $.valueview || {};

( function( vv ) {
	'use strict';

	/**
	 * Allows to receive information about a related `valueview` object but doesn't provide
	 * functions to actively change the view. It serves as a state object to inform subsystems of
	 * the `valueview`'s current status. Those subsystems should not have full access to the entire
	 * view though since interaction in both directions would very likely mess things up.
	 *
	 * @class ViewState
	 * @license GNU GPL v2+
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 * @since 0.1
	 *
	 * @constructor
	 *
	 * @param {jQuery.valueview} valueview
	 *
	 * @throws {Error} if no `jQuery.valueview` instance is provided.
	 */
	var SELF = function ValueviewViewState( valueview ) {
		if ( !( valueview instanceof vv.valueview ) ) {
			throw new Error( 'Can not create a valueview ViewState object without a valueview' );
		}
		this._view = valueview;
	};

	$.extend( SELF.prototype, {
		/**
		 * The widget object whose status is represented.
		 *
		 * @property {jQuery.valueview}
		 * @private
		 */
		_view: null,

		/**
		 * @see jQuery.valueview.isInEditMode
		 *
		 * @return {boolean}
		 */
		isInEditMode: function() {
			return this._view.isInEditMode();
		},

		/**
		 * Returns whether the related `valueview` is currently disabled.
		 *
		 * @return {boolean}
		 */
		isDisabled: function() {
			return this._view.option( 'disabled' );
		},

		/**
		 * Returns the related `valueview`'s current value. Does not allow to change the value.
		 *
		 * @see jQuery.valueview.value
		 *
		 * @return {dataValues.DataValue|null}
		 */
		value: function() {
			return this._view.value();
		},

		/**
		 * Returns the related `valueview`'s current formatted value.
		 *
		 * @see jQuery.valueview.getFormattedValue
		 *
		 * @return {string}
		 */
		getFormattedValue: function() {
			return this._view.getFormattedValue();
		},

		/**
		 * Returns the related `valueview`'s current plain text value.
		 *
		 * @see jQuery.valueview.getTextValue
		 * @since 0.4
		 *
		 * @return {string}
		 */
		getTextValue: function() {
			return this._view.getTextValue();
		},

		/**
		 * Returns the options or a specific option of the related `valueview`. Does not allow
		 * to set any option.
		 *
		 * @see jQuery.Widget.option
		 *
		 * @param {string} [key]
		 * @return {*}
		 */
		option: function( key ) {
			return this._view.option( key );
		}
	} );

module.exports = SELF;
}( $.valueview ) );
