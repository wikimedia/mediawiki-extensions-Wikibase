/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $, util ) {
'use strict';

/**
 * @constructor
 * @since 0.4
 */
var SELF = wb.datamodel.Groupable = function WbDataModelGroupable() {};

$.extend( SELF.prototype, {
	/**
	 * @return {*[]}
	 */
	toArray: util.abstractMember,

	/**
	 * @return {boolean}
	 */
	hasItem: util.abstractMember,

	/**
	 * @param {*} item
	 */
	addItem: util.abstractMember,

	/**
	 * @param {*} item
	 */
	removeItem: util.abstractMember,

	/**
	 * @return {boolean}
	 */
	isEmpty: util.abstractMember,

	/**
	 * @return {boolean}
	 */
	equals: util.abstractMember,

	/**
	 * @return {*}
	 */
	getItemKey: util.abstractMember
} );

}( wikibase, jQuery, util ) );
