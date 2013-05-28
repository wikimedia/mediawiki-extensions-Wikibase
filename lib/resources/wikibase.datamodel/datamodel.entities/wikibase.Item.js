/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb ) {
	'use strict';

	var PARENT = wb.Entity,
		SELF;

	/**
	 * Represents a Wikibase Item.
	 *
	 * @constructor
	 * @extends wb.Entity
	 * @since 0.4
	 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#Items
	 *
	 * @param {Object} data
	 */
	SELF = wb.Item = wb.utilities.inherit( 'WbItem', PARENT, {
		// TODO: implement sitelinks related getter/setter
	} );

	/**
	 * @see wb.Entity.TYPE
	 */
	SELF.TYPE = 'item';

}( wikibase ) );
