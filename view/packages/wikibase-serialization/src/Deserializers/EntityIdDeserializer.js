/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * Deserializer for EntityId objects.
 *
 * @constructor
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 */
MODULE.EntityIdDeserializer = util.inherit( 'WbEntityIdDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.EntityId}
	 */
	deserialize: function( serialization ) {
		return new wb.datamodel.EntityId(
			serialization['entity-type'],
			serialization['numeric-id']
		);
	}
} );

}( wikibase, util ) );
