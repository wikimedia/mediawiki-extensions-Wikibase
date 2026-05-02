( function( $, util ) {
'use strict';

/**
 * Interface defining interaction with a Group instance.
 * @class GroupableCollection
 * @abstract
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
var SELF = function WbDataModelGroupableCollection() {};

/**
 * @class GroupableCollection
 */
$.extend( SELF.prototype, {
	/**
	 * Returns the collection items as list.
	 * @abstract
	 *
	 * @return {*[]}
	 */
	toArray: util.abstractMember,

	/**
	 * Returns whether the collection contains a specific item.
	 * @abstract
	 *
	 * @param {*} item
	 * @return {boolean}
	 */
	hasItem: util.abstractMember,

	/**
	 * Adds an item to the collection.
	 * @abstract
	 *
	 * @param {*} item
	 */
	addItem: util.abstractMember,

	/**
	 * Removes an item from the collection.
	 * @abstract
	 *
	 * @param {*} item
	 */
	removeItem: util.abstractMember,

	/**
	 * Returns whether the collection contains any items.
	 * @abstract
	 *
	 * @return {boolean}
	 */
	isEmpty: util.abstractMember,

	/**
	 * Returns whether the collection is equal to another object.
	 * @abstract
	 *
	 * @param {*} groupableCollection
	 * @return {boolean}
	 */
	equals: util.abstractMember,

	/**
	 * Returns an item's key.
	 * @abstract
	 *
	 * @param {*} item
	 * @return {*}
	 */
	getItemKey: util.abstractMember
} );

module.exports = SELF;

}( jQuery, util ) );
