/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * Valueview expert for values of quantity data type.
 *
 * @since 0.1
 *
 * @constructor
 * @extends jQuery.valueview.experts.StringValue
 */
jQuery.valueview.experts.QuantityType = ( function( dv, $, vv ) {
	'use strict';

	var PARENT = vv.experts.StringValue;

	return vv.expert( 'quantitytype', PARENT, {
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
		}
	} );

}( dataValues, jQuery, jQuery.valueview ) );
