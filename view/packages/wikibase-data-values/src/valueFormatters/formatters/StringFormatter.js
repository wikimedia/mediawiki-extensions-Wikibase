( function( $, vf, util ) {
	'use strict';

	var PARENT = vf.ValueFormatter;

	/**
	 * String formatter
	 * @licence GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 * @extends valueFormatters.ValueFormatter
	 * @since 0.1
	 */
	vf.StringFormatter = util.inherit( PARENT, {
		/**
		 * @see valueFormatters.ValueFormatter.format
		 * @since 0.1
		 *
		 * @param {dataValues.StringValue} dataValue
		 * @return {$.Promise}
		 */
		format: function( dataValue ) {
			var deferred = $.Deferred();

			deferred.resolve( dataValue.toJSON(), dataValue );

			return deferred.promise();
		}
	} );

}( jQuery, valueFormatters, util ) );
