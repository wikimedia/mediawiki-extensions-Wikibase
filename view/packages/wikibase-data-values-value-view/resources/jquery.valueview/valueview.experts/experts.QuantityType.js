/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * Valueview expert for values of quantity data type.
 *
 * @since 0.1
 *
 * @constructor
 * @extends jQuery.valueview.experts.BifidExpert
 */
jQuery.valueview.experts.QuantityType = ( function( dv, vp, $, vv ) {
	'use strict';

	var PARENT = vv.StringValue;
//	var editableExpert = vv.experts.StringValue;

	return vv.expert( 'quantitytype', PARENT, {
		/**
		 * @see Query.valueview.Expert.parser
		 */
		parser: function() {
			return new vp.QuantityParser();
		}
	} );

}( dataValues, valueParsers, jQuery, jQuery.valueview ) );
