/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, $, mw ) {
	'use strict';

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
	var DEFAULT_LANGUAGE = ( mw && mw.config && mw.config.get( 'wgUserLanguage' ) ) || null;

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
	var SELF = wb.datamodel.Entity = function WbEntity( data ) {
		// check whether the Entity has a type, doesn't make sense to create an instance of wb.datamodel.Entity!
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
				requestedValues = values;

			if( $.isArray( languages ) ) {
				requestedValues = {};

				for( var i in languages ) {
					var lang = languages[ i ],
						value = values[ lang ];

					if( value !== undefined ) {
						requestedValues[ lang ] = values[ lang ];
					}
				}
			}

			return requestedValues;
		};
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
		 * @return wb.datamodel.Claim[]
		 */
		getClaims: newGetterFn( 'claims', [] ),

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
	 * @return wb.datamodel.Entity|null
	 */
	SELF.newFromMap = function( map ) {
		// TODO: allow for registration of new Entity types to this factory

		var data = $.extend( {}, map );
		delete( data.type );

		switch( map.type ) {
			case 'item':
				return new wb.datamodel.Item( data );
			case 'property':
				return new wb.datamodel.Property( data );
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
	 * @return wb.datamodel.Entity
	 */
	SELF.newEmpty = function( type ) {
		var entity = SELF.newFromMap( {
			type: type
		} );

		if( entity === null ) {
			throw new Error( 'Unknown entity type "' + type + '"' );
		}
		return entity;
	};

}( wikibase, jQuery, mediaWiki ) );
