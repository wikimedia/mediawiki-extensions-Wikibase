( function( $, vf, util ) {
	'use strict';

var PARENT = vf.ValueFormatter;

/**
 * String formatter.
 * @class valueFormatters.StringFormatter
 * @extends valueFormatters.ValueFormatter
 * @since 0.1
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
vf.StringFormatter = util.inherit( PARENT, {
	/**
	 * @inheritdoc
	 */
	format: function( dataValue ) {
		var deferred = $.Deferred();

		deferred.resolve( dataValue.toJSON(), dataValue );

		return deferred.promise();
	}
} );

}( jQuery, valueFormatters, util ) );
