/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
jQuery.valueview.experts.QuantityType = ( function( dv, vv ) {
	'use strict';

	var PARENT = vv.experts.StringValue;

	return vv.expert( 'QuantityType', PARENT, {

		/**
		 * Current raw value.
		 * @type {string}
		 * @TODO Evaluate whether caching the raw value in an attribute is the proper way to
		 * retrieve the current raw value and move mechanism to jQuery.valueview.Expert.
		 */
		_rawValue: null,

		/**
		 * @see jQuery.valueview.Expert._setRawValue
		 */
		_setRawValue: function( rawValue ) {
			if( rawValue instanceof dv.QuantityValue ) {
				rawValue = rawValue.getAmount().getValue();
			} else if( typeof rawValue !== 'string' ) {
				rawValue = null;
			}
			this._newValue = rawValue;
			this._rawValue = rawValue;
		},

		/**
		 * @see jQuery.valueview.Expert._getRawValue
		 */
		_getRawValue: function() {
			return ( this._viewState.isInEditMode() ) ? this.$input.val() : this._rawValue;
		},

		/**
		 * @see jQuery.valueview.Expert.draw
		 * @TODO Remove drawing non-edit mode state from the expert (see bug 56259)
		 */
		draw: function() {
			PARENT.prototype.draw.call( this );
			if( !this._viewState.isInEditMode() ) {
				this.$input.val( this._viewState.getFormattedValue() || '' );
			}
		}
	} );

}( dataValues, jQuery.valueview ) );
