/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util, $ ) {
	'use strict';

	var MODULE = wb.serialization,
		PARENT = MODULE.Deserializer;

	/**
	 * Deserializers for specific entity types.
	 * @type {Object}
	 */
	var typeSpecificDeserializers = {};

	/**
	 * Deserializer for Entity objects.
	 * @constructor
	 * @extends {wikibase.serialization.Deserializer}
	 * @since 1.0
	 */
	var SELF = MODULE.EntityDeserializer = util.inherit( 'WbEntityDeserializer', PARENT, {
		/**
		 * @see wb.serialization.Deserializer.deserialize
		 *
		 * @return {wikibase.datamodel.Entity}
		 */
		deserialize: function( serialization ) {
			var entityType = serialization.type;

			if( !entityType || typeof entityType !== 'string' ) {
				throw new Error( 'Can not determine type of Entity from serialized object' );
			}

			var typeSpecificDeserializer = typeSpecificDeserializers[entityType],
				typeSpecificData = {},
				fingerprintDeserializer = new MODULE.FingerprintDeserializer(),
				fingerprint = fingerprintDeserializer.deserialize( serialization ),
				statementGroupSetDeserializer = new MODULE.StatementGroupSetDeserializer(),
				statementGroupSet = statementGroupSetDeserializer.deserialize(
					serialization.claims
				);

			// extend map with data which is specific to the entity type if there is handling for
			// the entity type we are dealing with:
			if( typeSpecificDeserializer ) {
				typeSpecificDeserializer.setOptions( this._options );
				typeSpecificData = typeSpecificDeserializer.deserialize( serialization );
			}

			// TODO: Implement dedicated Deserializers and proper strategy
			if( entityType === 'property' ) {
				return new wb.datamodel.Property(
					serialization.id,
					typeSpecificData.datatype,
					fingerprint,
					statementGroupSet
				);
			} else if( entityType === 'item' ) {
				return new wb.datamodel.Item(
					serialization.id,
					fingerprint,
					statementGroupSet,
					typeSpecificData
				);
			}

			throw new Error( 'Deserializing entity type "' + entityType + '" is not supported' );
		}
	} );

	/**
	 * Allows registering individual deserialization logic for entities per entity type.
	 * The returned object is supposed to contain the data which is specific for the handled type
	 * of entity compared to the generic entity. The keys of the returned object should be what
	 * wikibase.datamodel.Entity.newFromMap requires to create a new Entity of the specific type.
	 *
	 * @since 1.0
	 *
	 * @param {string} entityType
	 * @param {Function} TypeSpecificDeserializer
	 */
	SELF.registerTypeSpecificExpert = function( entityType, TypeSpecificDeserializer ) {
		// for performance, we just create one instance of that deserializer and change its
		// options whenever we will use it
		typeSpecificDeserializers[ entityType ] = new TypeSpecificDeserializer();
	};

}( wikibase, util, jQuery ) );
