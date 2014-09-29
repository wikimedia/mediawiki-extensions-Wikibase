/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util, $ ) {
	'use strict';

	var MODULE = wb.serialization,
		PARENT = MODULE.Serializer;

	/**
	 * Serializers for specific entity types.
	 * @type {Object}
	 */
	var typeSpecificSerializers = {};

	/**
	 * Serializer for Entity objects.
	 * @constructor
	 * @extends {wikibase.serialization.Serializer}
	 * @since 2.0
	 */
	var SELF = MODULE.EntitySerializer = util.inherit( 'WbEntitySerializer', PARENT, {
		/**
		 * @see wb.serialization.Serializer.serialize
		 *
		 * @param {wikibase.datamodel.Entity} entity
		 * @return {Object}
		 */
		serialize: function( entity ) {
			if( !( entity instanceof wb.datamodel.Entity ) ) {
				throw new Error( 'Not an instance of wikibase.datamodel.Entity' );
			}

			var entityType = entity.getType(),
				typeSpecificSerializer = typeSpecificSerializers[entityType],
				fingerprintSerializer = new MODULE.FingerprintSerializer(),
				statementGroupSetSerializer = new MODULE.StatementGroupSetSerializer();

			var serialization = $.extend( true,
				{
					type: entityType,
					id: entity.getId(),
					claims: statementGroupSetSerializer.serialize( entity.getStatements() )
				},
				fingerprintSerializer.serialize( entity.getFingerprint() )
			);

			if( typeSpecificSerializer ) {
				var typeSpecificSerialization = typeSpecificSerializer.serialize( entity );

				$.extend( serialization, typeSpecificSerialization );
			}

			return serialization;
		}
	} );

	/**
	 * Allows registering individual serialization logic for entities per entity type.
	 *
	 * @param {string} entityType
	 * @param {Function} TypeSpecificSerializer
	 */
	SELF.registerTypeSpecificExpert = function( entityType, TypeSpecificSerializer ) {
		// Just create one instance:
		typeSpecificSerializers[entityType] = new TypeSpecificSerializer();
	};

}( wikibase, util, jQuery ) );
