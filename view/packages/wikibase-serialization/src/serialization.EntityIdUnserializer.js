/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for EntityId objects.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 1.2
 */
MODULE.EntityIdUnserializer = util.inherit( 'WbEntityIdUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.EntityId}
	 */
	unserialize: function( serialization ) {
		return new wb.datamodel.EntityId(
			serialization['entity-type'],
			serialization['numeric-id']
		);
	}
} );

}( wikibase, util ) );
