/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util, $ ) {
	'use strict';

	var MODULE = wb.serialization,
		PARENT = MODULE.Unserializer;

	/**
	 * Unserializers for specific entity types.
	 * @type {Object}
	 */
	var typeSpecificUnserializers = {};

	/**
	 * Unserializer for Entity objects.
	 * @constructor
	 * @extends {wikibase.serialization.Unserializer}
	 * @since 1.0
	 */
	var SELF = MODULE.EntityUnserializer = util.inherit( 'WbEntityUnserializer', PARENT, {
		/**
		 * @see wb.serialization.Unserializer.unserialize
		 *
		 * @return {wikibase.datamodel.Entity}
		 */
		unserialize: function( serialization ) {
			var entityType = serialization.type;

			if( !entityType || typeof entityType !== 'string' ) {
				throw new Error( 'Can not determine type of Entity from serialized object' );
			}

			var typeSpecificUnserializer = typeSpecificUnserializers[entityType],
				typeSpecificData = {},
				fingerprintUnserializer = new MODULE.FingerprintUnserializer(),
				fingerprint = fingerprintUnserializer.unserialize( serialization ),
				statementGroupSetUnserializer = new MODULE.StatementGroupSetUnserializer(),
				statementGroupSet = statementGroupSetUnserializer.unserialize(
					serialization.claims
				);

			// extend map with data which is specific to the entity type if there is handling for
			// the entity type we are dealing with:
			if( typeSpecificUnserializer ) {
				typeSpecificUnserializer.setOptions( this._options );
				typeSpecificData = typeSpecificUnserializer.unserialize( serialization );
			}

			// TODO: Implement dedicated Unserializers and proper strategy
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

			throw new Error( 'Unserializing entity type "' + entityType + '" is not supported' );
		}
	} );

	/**
	 * Allows registering individual unserialization logic for entities per entity type.
	 * The returned object is supposed to contain the data which is specific for the handled type
	 * of entity compared to the generic entity. The keys of the returned object should be what
	 * wikibase.datamodel.Entity.newFromMap requires to create a new Entity of the specific type.
	 *
	 * @since 1.0
	 *
	 * @param {string} entityType
	 * @param {Function} TypeSpecificUnserializer
	 */
	SELF.registerTypeSpecificExpert = function( entityType, TypeSpecificUnserializer ) {
		// for performance, we just create one instance of that unserializer and change its
		// options whenever we will use it
		typeSpecificUnserializers[ entityType ] = new TypeSpecificUnserializer();
	};

}( wikibase, util, jQuery ) );
