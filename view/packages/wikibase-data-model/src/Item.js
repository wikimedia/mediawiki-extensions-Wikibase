/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util ) {
	'use strict';

	var PARENT = wb.datamodel.Entity;

	/**
	 * Represents a Wikibase Item.
	 *
	 * @constructor
	 * @extends wb.datamodel.Entity
	 * @since 0.4
	 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#Items
	 *
	 * @param {Object} data
	 */
	var SELF = wb.datamodel.Item = util.inherit( 'WbItem', PARENT, {
		// TODO: implement sitelinks related getter/setter
	} );

	/**
	 * @see wb.datamodel.Entity.TYPE
	 */
	SELF.TYPE = 'item';

}( wikibase, util ) );
