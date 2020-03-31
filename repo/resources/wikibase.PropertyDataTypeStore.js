/**
 * @license GPL-2.0-or-later
 */
( function () {

	/**
	 * @param {Object} entityLoadedHook
	 * @param {EntityStore} entityStore
	 */
	function PropertyDataTypeStore( entityLoadedHook, entityStore ) {
		this._entityLoadedHook = entityLoadedHook;
		this._entityStore = entityStore;
		this._propertyDataTypeMapping = {};
	}

	$.extend( PropertyDataTypeStore.prototype, {
		/**
		 * @type {Object} map of property id to data type
		 */
		_propertyDataTypeMapping: {},

		/**
		 * @type {Object}
		 */
		_entityLoadedHook: null,

		/**
		 * @type {EntityStore}
		 */
		_entityStore: null,

		setDataTypeForProperty: function ( id, dataType ) {
			this._propertyDataTypeMapping[ id ] = dataType;
		},

		getDataTypeForProperty: function ( id ) {
			if ( this._propertyDataTypeMapping[ id ] ) {
				return $.Deferred().resolve( this._propertyDataTypeMapping[ id ] );
			}

			return this._getDataTypeFromExistingStatements( id )
				.catch( this._getDataTypeFromEntityStore.bind( this, id ) );
		},

		_getDataTypeFromExistingStatements: function ( propertyId ) {
			var dataTypePromise = $.Deferred();

			this._entityLoadedHook.add( function ( entity ) {
				var dataType = entity.claims[ propertyId ] && entity.claims[ propertyId ][ 0 ].mainsnak.datatype;
				if ( dataType ) {
					dataTypePromise.resolve( dataType );
				} else {
					dataTypePromise.reject();
				}
			} );

			return dataTypePromise;
		},

		_getDataTypeFromEntityStore: function ( propertyId ) {
			return this._entityStore.get( propertyId ).then( function ( property ) {
				if ( !property ) {
					return null;
				}

				return property.getDataTypeId();
			} );
		}
	} );

	module.exports = PropertyDataTypeStore;

}() );
