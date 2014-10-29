/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $, util ) {
'use strict';

/**
 * @constructor
 * @since 1.0
 * @abstract
 */
var SELF = wb.datamodel.GroupableCollection = function WbDataModelGroupableCollection() {};

$.extend( SELF.prototype, {
	/**
	 * Returns a copy the grouped items as list.
	 *
	 * @return {*[]}
	 */
	toArray: util.abstractMember,

	/**
	 * Returns whether the group contains a specific item.
	 *
	 * @param {*} item
	 * @return {boolean}
	 */
	hasItem: util.abstractMember,

	/**
	 * Adds an item to the group.
	 *
	 * @param {*} item
	 */
	addItem: util.abstractMember,

	/**
	 * Removes an item from the group.
	 *
	 * @param {*} item
	 */
	removeItem: util.abstractMember,

	/**
	 * Returns whether the group contains any items.
	 *
	 * @return {boolean}
	 */
	isEmpty: util.abstractMember,

	/**
	 * Returns whether the group is equal to another object.
	 *
	 * @param {*}
	 * @return {boolean}
	 */
	equals: util.abstractMember,

	/**
	 * Returns an item's key.
	 *
	 * @param {*} item
	 * @return {*}
	 */
	getItemKey: util.abstractMember
} );

}( wikibase, jQuery, util ) );
