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
	 * @type wb.serialization.Unserializer
	 */
	var typeSpecificUnserializers = {};

	/**
	 * @param {Object[]} array
	 * @return {*[]}
	 */
	function extractValuesFromObjectArray( array ) {
		return $.map( array, function( valueObj ) {
			return valueObj.value;
		} );
	}

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
		var unserialized = {};

		for( var lang in serialization ) {
			unserialized[lang] = $.isArray( serialization[lang] )
				? extractValuesFromObjectArray( serialization[lang] )
				: serialization[lang].value;
		}

		return unserialized;
	}

	/**
	 * Helper for unserializing an Entity's claims.
	 *
	 * TODO: we should probably have a ClaimList which then has its own unserializer.
	 *
	 * @param {Object} serialization
	 * @return wb.datamodel.Claim[]
	 */
	function unserializeClaims( serialization ) {
		var claims = [];

		// get claims:
		for( var propId in serialization || {} ) {
			var claimsPerProp = serialization[ propId ];

			for( var i in claimsPerProp ) {
				var serializedClaim = claimsPerProp[ i ],
					// TODO: use ClaimUnserializer here after it got implemented
					claim = wb.datamodel.Claim.newFromJSON( serializedClaim );

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
	var SELF = MODULE.EntityUnserializer = util.inherit( 'WbEntityUnserializer', PARENT, {
		/**
		 * @see wb.serialization.Unserializer.unserialize
		 *
		 * @return wb.datamodel.Entity
		 */
		unserialize: function( serialization ) {
			var entityType = serialization.type,
				typeSpecificUnserializer = typeSpecificUnserializers[ entityType ],
				multilangualUnserializer = new MODULE.MultilingualUnserializer();

			if( !entityType || typeof entityType !== 'string' ) {
				throw new Error( 'Can not determine type of Entity from serialized object' );
			}

			// create map with data which is the same for all types of entities:
			var entityMapData = {
				type: entityType,
				id: serialization.id,
				title: serialization.title,
				label: multilangualUnserializer.unserialize( serialization.labels ),
				description: multilangualUnserializer.unserialize( serialization.descriptions ),
				aliases: multilangualUnserializer.unserialize( serialization.aliases ),
				claims: unserializeClaims( serialization.claims )
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
	 * Allows to register advanced unserialization logic for a certain type of Entity. Takes the
	 * type the additional handling is required for and a Unserializer object which has the job to
	 * return the type specific map data as Object. The Object keys should contain the data which
	 * is different for the handled type of entity compared to other entity types. The keys should
	 * be what wb.datamodel.Entity.newFromMap requires to create a new Entity of the specific type.
	 *
	 * @since 0.4
	 *
	 * @param {string} entityType
	 * @param {Function} TypeSpecificUnserializer Constructor which inherits from
	 *        wb.serialization.Unserializer.
	 */
	SELF.registerTypeSpecificExpert = function( entityType, TypeSpecificUnserializer ) {
		// for performance, we just create one instance of that unserializer and change its
		// options whenever we will use it
		typeSpecificUnserializers[ entityType ] = new TypeSpecificUnserializer();
	};

}( wikibase, util, jQuery ) );
