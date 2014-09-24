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
			var entityType = serialization.type,
				typeSpecificUnserializer = typeSpecificUnserializers[ entityType ],
				multilingualUnserializer = new MODULE.MultilingualUnserializer(),
				claimsUnserializer = new MODULE.ClaimsUnserializer();

			if( !entityType || typeof entityType !== 'string' ) {
				throw new Error( 'Can not determine type of Entity from serialized object' );
			}

			// create map with data which is the same for all types of entities:
			var entityMapData = {
				type: entityType,
				id: serialization.id,
				// TODO: Remove title since it is not part of native serialization format
				title: serialization.title,
				label: multilingualUnserializer.unserialize( serialization.labels ),
				description: multilingualUnserializer.unserialize( serialization.descriptions ),
				aliases: multilingualUnserializer.unserialize( serialization.aliases ),
				claims: claimsUnserializer.unserialize( serialization.claims )
			};

			// extend map with data which is specific to the entity type if there is handling for
			// the entity type we are dealing with:
			if( typeSpecificUnserializer ) {
				typeSpecificUnserializer.setOptions( this._options );
				var typeSpecificData = typeSpecificUnserializer.unserialize( serialization );

				// merge type specific data with ordinary data
				$.extend( entityMapData, typeSpecificData );
			}

			return wb.datamodel.Entity.newFromMap(
				entityMapData
			);
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
