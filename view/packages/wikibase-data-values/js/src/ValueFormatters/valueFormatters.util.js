/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, vf, dv ) {
	'use strict';

	/**
	 * Module for utilities of the ValueFormatters extension.
	 * @since 0.1
	 * @type {Object}
	 */
	vf.util = {};

	/**
	 * @see dataValues.util.inherit
	 * @since 0.1
	 */
	vf.util.inherit = dv.util.inherit;

	/**
	 * @see dataValues.util.abstractMember
	 * @since 0.1
	 */
	vf.util.abstractMember = dv.util.abstractMember;

}( jQuery, valueFormatters, dataValues ) );
