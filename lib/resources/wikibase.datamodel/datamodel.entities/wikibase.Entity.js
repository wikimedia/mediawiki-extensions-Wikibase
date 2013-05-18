/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';
	var DEFAULT_LANGUAGE, SELF;

	/**
	 * If used in MediaWiki context, then this will hold the user's language so Entity data can
	 * be returned in that language if no other language has been given.
	 *
	 * TODO: this really isn't all that nice since this is relying on the mw global and will lead
	 *  to other bad practice, not passing the language to the Entity's functions. We should get
	 *  rid of this rather sooner than later.
	 *
	 * @type string|null
	 */
	DEFAULT_LANGUAGE = mediaWiki && mediaWiki.config ?
		( mediaWiki.config.get( 'wgUserLanguage' ) || null ) :
		null;

	/**
	 * Represents a Wikibase Entity.
	 *
	 * @constructor
	 * @abstract
	 * @since 0.4
	 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#Entities
	 *
	 * @param {Object} data
	 *
	 * TODO: implement setter functions
	 */
	SELF = wb.Entity = function WbEntity( data ) {
		// check whether the Entity has a type, doesn't make sense to create an instance of wb.Entity!
		if( !this.constructor.TYPE ) {
			throw new Error( 'Can not create abstract Entity of no specific type' );
		}
		if( data && !$.isPlainObject( data ) ) {
			throw new Error( 'Entity constructor data has to be a plain object' );
		}
		// Don't just save this by reference! By deep copying, also make sure arrays storing
		// multi-lingual values won't end up still being references to the outside world!
		this._data = $.extend( true, {}, data );
	};

	/**
	 * String to identify this type of Entity.
	 * @since 0.4
	 * @type String
	 */
	SELF.TYPE = null;

	/**
	 * Helper to build a getter function for getting a specific property of the entity.
	 *
	 * @param {string} field The field holding the information in the internal data representation.
	 * @param {*} defaultReturnValue What will be returned if field is empty
	 * @return Function
	 */
	function newGetterFn( field, defaultReturnValue ) {
		return function() {
			return this._data[ field ] || defaultReturnValue;
		};
	}

	/**
	 * Helper to build a getter function for getting a specific multi-lingual property of the entity.
	 *
	 * @param {string} field The field holding the information in the internal data representation.
	 * @return Function
	 */
	function newByLangGetterFn( field ) {
		return function( languageCode ) {
			if( languageCode === undefined ) {
				languageCode = DEFAULT_LANGUAGE;
			}
			if( typeof languageCode !== 'string' ) {
				throw new Error( 'Language code required to select a ' + field );
			}
			return ( this._data[ field ] || {} )[ languageCode ] || null;
		};
	}

	/**
	 * Helper to build a getter function for getting a specific multi-lingual property of the entity
	 * in all languages or in a given set of languages.
	 *
	 * @param {string} field The field holding the information in the internal data representation.
	 * @return Function
	 */
	function newByLangListGetterFn( field ) {
		return function( languages ) {
			var values = this._data[ field ]  || {},
				requestedValues = values,
				i, lang, value;

			if( $.isArray( languages ) ) {
				requestedValues = {};

				for( i in languages ) {
					lang = languages[ i ];
					value = values[ lang ];

					if( value !== undefined ) {
						requestedValues[ lang ] = values[ lang ];
					}
				}
			}

			return requestedValues;
		};
	}

	/**
	 * Helper function to compare whether two entities have the same values for a multi-lingual
	 * property.
	 *
	 * @param {string} fieldAccessor wb.Entity's function name to get the fields values.
	 * @param {wb.Entity} entity1
	 * @param {wb.Entity} entity2
	 * @return boolean
	 */
	function entitiesHaveEqualMultiLingualField( entity1, entity2, fieldFnName ) {
		var fields1 = entity1[ fieldFnName ](),
			fields2 = entity2[ fieldFnName ](),
			fields1length = 0,
			fields2length = 0,
			isHandlingAliases = fieldFnName === 'getAllAliases',
			i, lang, langValues1, langValues2;

		for( i in fields1 ) {
			fields1length++;
		}

		for( lang in fields2 ) {
			langValues1 = fields1[ lang ];
			langValues2 = fields2[ lang ];

			if( !isHandlingAliases ) {
				if( langValues1 !== langValues2 ) {
					return false;
				}
			} else {
				// We're dealing with aliases. Aliases of one language are represented as array.
				langValues1 = $( langValues1 );
				langValues2 = $( langValues2 );

				if( langValues1.length !== langValues2.length ||
					langValues1.not( langValues2 ).length !== 0 ||
					langValues2.not( langValues1 ).length !== 0
				) {
					return false;
				}
			}
			fields2length++;
		}

		return fields1length === fields2length;
	}

	$.extend( SELF.prototype, {
		/**
		 * Internal representation of the object.
		 * @type Object
		 */
		_data: null,

		/**
		 * Returns whether the Entity is considered to be a new one. This is the case if the
		 * Entity has no ID yet, which will imply that the store has not yet seen this Entity.
		 *
		 * @since 0.4
		 *
		 * @return {Boolean}
		 */
		isNew: function() {
			return this.getId() === null;
		},

		/**
		 * Returns what type of Entity this is.
		 *
		 * @since 0.4

		 * @return string
		 */
		getType: function() {
			return this.constructor.TYPE;
		},

		/**
		 * Returns the Entity's ID.
		 *
		 * @since 0.4
		 *
		 * @return string|null
		 */
		getId: newGetterFn( 'id', null ),

		/**
		 * Returns the Entity's label in a specified language or null if not available in that
		 * language.
		 *
		 * @since 0.4
		 *
		 * @param {string} languageCode
		 * @return string|null
		 */
		getLabel: newByLangGetterFn( 'label' ),

		/**
		 * Returns the Entity's label in all languages or in a set of specified languages.
		 *
		 * @since 0.4
		 *
		 * @param {string[]} languageCode Language codes
		 * @return {Object} With language codes as keys. If a given code has no value, then the
		 *         code will not be set in the object.
		 */
		getLabels: newByLangListGetterFn( 'label' ),

		/**
		 * Returns the Entity's description in a specified language or null if not available in that
		 * language.
		 *
		 * @since 0.4
		 *
		 * @param {string} languageCode
		 * @return string|null
		 */
		getDescription: newByLangGetterFn( 'description' ),

		/**
		 * Returns the Entity's description in all languages or in a set of specified languages.
		 *
		 * @since 0.4
		 *
		 * @param {string[]} languageCode Language codes
		 * @return {Object} With language codes as keys. If a given code has no value, then the
		 *         code will not be set in the object.
		 */
		getDescriptions: newByLangListGetterFn( 'description' ),

		/**
		 * Returns the Entity's aliases in a specified language or null if not available in that
		 * language.
		 *
		 * @since 0.4
		 *
		 * @param {string} languageCode
		 * @return string[]|null
		 */
		getAliases: newByLangGetterFn( 'aliases' ),

		/**
		 * Returns the Entity's aliases in all languages or in a set of specified languages.
		 *
		 * @since 0.4
		 *
		 * @param {string[]} languageCode Language codes
		 * @return {Object} With language codes as keys. If a given code has no value, then the
		 *         code will not be set in the object.
		 */
		getAllAliases: newByLangListGetterFn( 'aliases' ),

		/**
		 * Returns the Entity's claims.
		 *
		 * @since 0.4
		 *
		 * @return wb.Claim[]
		 */
		getClaims: newGetterFn( 'claims', [] ),

		/**
		 * Returns whether this Entity is equal to another Entity. Two Entities are considered equal
		 * if they are of the same type and have the same value. The value does not include the id,
		 * so Entities with the same value but different id are still considered equal.
		 *
		 * @since 0.4
		 *
		 * @param {wb.Entity|*} that If this is not a wb.Entity, false will be returned.
		 * @return boolean
		 */
		equals: function( that ) {
			if( this === that ) {
				return true;
			}

			// we ignore the ID here as stated in the description!

			if( !( that instanceof SELF ) ||
				this.getType() !== that.getType()
			) {
				return false;
			}

			if( !entitiesHaveEqualMultiLingualField( this, that, 'getLabels' ) ||
				!entitiesHaveEqualMultiLingualField( this, that, 'getDescriptions' ) ||
				!entitiesHaveEqualMultiLingualField( this, that, 'getAllAliases' )
			) {
				return false;
			}

			// everything else is equal, check for same number of claims and equal claims:
			var ownClaims = this.getClaims(),
				otherClaims = that.getClaims(),
				i, j, hasEqualClaim, claim;

			if( ownClaims.length !== otherClaims.length ) {
				return false;
			}

			for( i in ownClaims ) {
				claim = ownClaims[ i ];
				hasEqualClaim = false;

				for( j in otherClaims ) {
					if( claim.equals( otherClaims[j] ) ) {
						hasEqualClaim = true;
						break;
					}
				}
				if( !hasEqualClaim ) {
					return false;
				}
			}

			return true;
		},

		/**
		 * Returns whether this Entity is the same as some other Entity. Two entities are considered
		 * the same if both have the same ID and equal content. If at least one of the Entities has
		 * no ID, then they can not be considered the same.
		 *
		 * @since 0.1
		 *
		 * @param {wb.Entity|*} that
		 * @return {Boolean}
		 */
		isSameAs: function( that ) {
			if( !( that instanceof SELF ) ) {
				return false;
			}

			// "new" entity has no identity, so it simply can't be the same as any other Entity.
			// Also, if IDs are different, we already know that they are not the same.
			if( this.isNew() || that.isNew() ||
				this.getId() !== that.getId()
			) {
				return false;
			}
			return this.equals( that );
		},

		/**
		 * Returns a plain Object representing this Entity. The fields of the object are similar to
		 * a serialized version of the Entity but values within the fields will not be serialized.
		 *
		 * @since 0.4
		 *
		 * @return Object
		 */
		toMap: function() {
			var map = $.extend( true, {}, this._data );
			map.type = this.getType();
			return map;
		}
	} );

	/**
	 * Creates a new Entity Object from a given Object with certain keys and values, what an actual
	 * Entity would return when calling its toMap().
	 *
	 * @since 0.4
	 *
	 * @param {Object} map Requires at least 'snaktype' and 'property' fields.
	 * @return wb.Entity|null
	 */
	SELF.newFromMap = function( map ) {
		// TODO: allow for registration of new Entity types to this factory

		var data = $.extend( {}, map );
		delete( data.type );

		switch( map.type ) {
			case 'item':
				return new wb.Item( data );
			case 'property':
				if( data.datatype ) {
					data.datatype = data.datatype.getId();
				}
				return new wb.Property( data );
			default:
				return null;
		}
	};

	/**
	 * Returns a new, empty entity of the given type.
	 *
	 * @since 0.4
	 *
	 * @param {string} type Entity type
	 * @return wb.Entity
	 */
	SELF.newEmpty = function( type ) {
		var entity = SELF.newFromMap( {
			type: type
		} );

		if( entity === null ) {
			throw new Error( 'Unkown entity type "' + type + '"' );
		}
		return entity;
	};

}( wikibase, jQuery ) );
