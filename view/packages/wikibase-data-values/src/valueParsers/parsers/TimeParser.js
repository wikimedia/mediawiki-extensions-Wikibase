( function( vp, dv, $, util ) {
	'use strict';

	var PARENT = vp.ValueParser;

	/**
	 * Constructor for time parsers.
	 * @class valueParsers.TimeParser
	 * @extends valueParsers.ValueParser
	 * @since 0.1
	 * @licence GNU GPL v2+
	 * @author Daniel Werner < danweetz@web.de >
	 *
	 * @constructor
	 */
	vp.TimeParser = util.inherit( PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @param {time.Time} time
		 */
		parse: function( time ) {
			return $.Deferred().resolve( new dv.TimeValue( time ) ).promise();
		}
	} );

}( valueParsers, dataValues, jQuery, util ) );
