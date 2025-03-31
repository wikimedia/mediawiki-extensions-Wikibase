/**
 * @license GPL-2.0-or-later
 */
( function () {

	class PropertyDataTypeStore {

		/**
		 * @param {Object} entityLoadedHook
		 * @param {EntityStore} entityStore
		 */
		constructor( entityLoadedHook, entityStore ) {
			/**
			 * @type {Object}
			 */
			this._entityLoadedHook = entityLoadedHook;
			/**
			 * @type {EntityStore}
			 */
			this._entityStore = entityStore;
			/**
			 * @type {Object} map of property id to data type
			 */
			this._propertyDataTypeMapping = {};
		}

		setDataTypeForProperty( id, dataType ) {
			this._propertyDataTypeMapping[ id ] = dataType;
		}

		getDataTypeForProperty( id ) {
			var self = this;

			if ( this._propertyDataTypeMapping[ id ] ) {
				return $.Deferred().resolve( this._propertyDataTypeMapping[ id ] );
			}

			return this._getDataTypeFromExistingStatements( id )
				.catch( this._getDataTypeFromEntityStore.bind( this, id ) )
				.always( ( dataType ) => {
					self.setDataTypeForProperty( id, dataType );
				} );
		}

		_getDataTypeFromExistingStatements( propertyId ) {
			var dataTypePromise = $.Deferred(),
				self = this;

			this._entityLoadedHook.add( ( entity ) => {
				var dataType = self._findDataTypeInEntity( entity, propertyId );
				if ( dataType ) {
					dataTypePromise.resolve( dataType );
				} else {
					dataTypePromise.reject();
				}
			} );

			return dataTypePromise;
		}

		/**
		 * Recursively traverses (pieces of) entity JSON and returns a property's data type if there is a statement for
		 * it on the entity.
		 *
		 * @param {Object} node
		 * @param {string} propertyId
		 *
		 * @return {null|string}
		 */
		_findDataTypeInEntity( node, propertyId ) {
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
		}

		_getDataTypeFromSnak( snak ) {
			return snak && snak.datatype || // if it's a qualifier/reference, the data type is at the top level
				snak.mainsnak && snak.mainsnak.datatype; // main snak
		}

		_getDataTypeFromEntityStore( propertyId ) {
			return this._entityStore.get( propertyId ).then( ( property ) => {
				if ( !property ) {
					return null;
				}

				return property.getDataTypeId();
			} );
		}
	}

	module.exports = PropertyDataTypeStore;

}() );
