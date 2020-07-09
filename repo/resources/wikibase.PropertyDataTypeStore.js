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
			var self = this;

			if ( this._propertyDataTypeMapping[ id ] ) {
				return $.Deferred().resolve( this._propertyDataTypeMapping[ id ] );
			}

			return this._getDataTypeFromExistingStatements( id )
				.catch( this._getDataTypeFromEntityStore.bind( this, id ) )
				.always( function ( dataType ) {
					self.setDataTypeForProperty( id, dataType );
				} );
		},

		_getDataTypeFromExistingStatements: function ( propertyId ) {
			var dataTypePromise = $.Deferred(),
				self = this;

			this._entityLoadedHook.add( function ( entity ) {
				var dataType = self._findDataTypeInEntity( entity, propertyId );
				if ( dataType ) {
					dataTypePromise.resolve( dataType );
				} else {
					dataTypePromise.reject();
				}
			} );

			return dataTypePromise;
		},

		/**
		 * Recursively traverses (pieces of) entity JSON and returns a property's data type if there is a statement for
		 * it on the entity.
		 *
		 * @param {Object} node
		 * @param {string} propertyId
		 *
		 * @return {null|string}
		 */
		_findDataTypeInEntity: function ( node, propertyId ) {
			if ( !node || typeof node !== 'object' ) {
				return null;
			}

			for ( var i in node ) {
				if ( i === propertyId ) {
					var dataTypeFromSnak = this._getDataTypeFromSnak( node[ i ][ 0 ] ); // may not exist (T249206)
					if ( dataTypeFromSnak ) {
						return dataTypeFromSnak;
					}
				}

				var dataType = this._findDataTypeInEntity( node[ i ], propertyId );
				if ( dataType ) {
					return dataType;
				}
			}

			return null;
		},

		_getDataTypeFromSnak: function ( snak ) {
			return snak && snak.datatype || // if it's a qualifier/reference, the data type is at the top level
				snak.mainsnak && snak.mainsnak.datatype; // main snak
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
