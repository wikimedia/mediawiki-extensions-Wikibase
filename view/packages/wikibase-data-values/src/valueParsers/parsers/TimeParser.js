( function( vp, dv, $, util ) {
	'use strict';

	var PARENT = vp.ValueParser;

	/**
	 * Constructor for time parsers.
	 * @licence GNU GPL v2+
	 * @author Daniel Werner < danweetz@web.de >
	 *
	 * @constructor
	 * @extends valueParsers.ValueParser
	 * @since 0.1
	 */
	vp.TimeParser = util.inherit( PARENT, {
		/**
		 * @inheritdoc
		 * @since 0.1
		 *
		 * @param {time.Time} time
		 * @return $.Promise
		 */
		parse: function( time ) {
			var deferred = $.Deferred().resolve( new dv.TimeValue( time ) );
			return deferred.promise();
		}
	} );

}( valueParsers, dataValues, jQuery, util ) );
