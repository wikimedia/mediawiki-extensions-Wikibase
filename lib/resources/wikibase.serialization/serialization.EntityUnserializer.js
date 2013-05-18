/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.serialization,
		PARENT = MODULE.Unserializer,
		typeSpecificUnserializers,
		SELF;

	/**
	 * Unserializers for specific entity types.
	 * @type wb.serialization.Unserializer
	 */
	typeSpecificUnserializers = {};

	/**
	 * Helper for unserializing multilingual value.
	 *
	 * @param {Object} serialization
	 * @return {Object} Map with language codes as fields
	 */
	function unserializeMultilingualValue( serialization ) {
		if( !serialization ) {
			return {};
		}
		var unserialized = {},
			lang;

		for( lang in serialization ) {
			unserialized[ lang ] = serialization[ lang ].value;
		}
		return unserialized;
	}

	/**
	 * Helper for unserializing an Entity's claims.
	 *
	 * TODO: we should probably have a ClaimList which then has its own unserializer.
	 *
	 * @param {Object} serialization
	 * @return wb.Claim[]
	 */
	function unserializeClaims( serialization ) {
		var claims = [],
			claim, propId, claimsPerProp, i, serializedClaim;

		// get claims:
		for( propId in serialization || {} ) {
			claimsPerProp = serialization[ propId ];

			for( i in claimsPerProp ) {
				serializedClaim = claimsPerProp[ i ];
				// TODO: use ClaimUnserializer here after it got implemented
				claim = wb.Claim.newFromJSON( serializedClaim );

				claims.push( claim );
			}
		}
		return claims;
	}

	/**
	 * Unserializer for Property entities.
	 *
	 * @constructor
	 * @extends wb.Unserializer
	 * @since 0.4
	 */
	SELF = MODULE.EntityUnserializer = wb.utilities.inherit( 'WbEntityUnserializer', PARENT, {
		/**
		 * @see wb.serialization.Unserializer.unserialize
		 *
		 * @return wb.Entity
		 */
		unserialize: function( serialization ) {
			var entityType = serialization.type,
				typeSpecificUnserializer = typeSpecificUnserializers[ entityType ],
				entityMapData, typeSpecificData;

			if( !entityType || typeof entityType !== 'string' ) {
				throw new Error( 'Can not determine type of Entity from serialized object' );
			}

			// create map with data which is the same for all types of entities:
			entityMapData = {
				type: entityType,
				id: serialization.id,
				label: unserializeMultilingualValue( serialization.labels ),
				description: unserializeMultilingualValue( serialization.descriptions ),
				aliases: unserializeMultilingualValue( serialization.aliases ),
				claims: unserializeClaims( serialization.claims )
			};

			// extend map with data which is specific to the entity type if there is handling for
			// the entity type we are dealing with:
			if( typeSpecificUnserializer ) {
				typeSpecificUnserializer.setOptions( this._options );
				typeSpecificData = typeSpecificUnserializer.unserialize( serialization );

				// merge type specific data with ordinary data
				$.extend( entityMapData, typeSpecificData );
			}

			return wb.Entity.newFromMap(
				entityMapData
			);
		}
	} );

	// register in SerializationFactory for wb.Entity unserialization handling:
	MODULE.SerializerFactory.registerUnserializer( SELF, wb.Entity );

	/**
	 * Allows to register advanced unserialization logic for a certain type of Entity. Takes the
	 * type the additional handling is required for and a Unserializer object which has the job to
	 * return the type specific map data as Object. The Object keys should contain the data which
	 * is different for the handled type of entity compared to other entity types. The keys should
	 * be what wb.Entity.newFromMap requires to create a new Entity of the specific type.
	 *
	 * @since 0.4
	 *
	 * @param {string} entityType
	 * @param {Function} typeSpecificUnserializer Constructor which inherits from
	 *        wb.serialization.Unserializer.
	 */
	SELF.registerTypeSpecificExpert = function( entityType, TypeSpecificUnserializer ) {
		// for performance, we just create one instance of that unserializer and change its
		// options whenever we will use it
		typeSpecificUnserializers[ entityType ] = new TypeSpecificUnserializer();
	};

	/**
	 * Returns the constructor of an Entity type specific unserialization expert previously
	 * registered via registerTypeSpecificExpert(). If none has been registered, null will be
	 * returned instead.
	 *
	 * @since 0.4
	 *
	 * @param {string} entityType
	 * @return Function|null
	 */
	SELF.getTypeSpecificExpert = function( entityType ) {
		var expert = typeSpecificUnserializers[ entityType ];
		if( !expert ) {
			return null;
		}
		return expert.constructor;
	};

}( wikibase, jQuery ) );
