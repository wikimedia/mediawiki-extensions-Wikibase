/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.EntityIdSerializer = util.inherit( 'WbEntityIdSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.EntityId} entityId
	 * @return {Object}
	 */
	serialize: function( entityId ) {
		if( !( entityId instanceof wb.datamodel.EntityId ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.EntityId' );
		}

		return [entityId.getEntityType(), entityId.getSerialization()];
	}
} );

}( wikibase, util ) );
