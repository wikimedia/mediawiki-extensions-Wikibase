/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, dt ) {
	'use strict';

	var PARENT = wb.Entity,
		SELF;

	/**
	 * Represents a Wikibase Property.
	 *
	 * @constructor
	 * @extends wb.Entity
	 * @since 0.4
	 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#Properties
	 *
	 * @param {Object} data
	 *
	 * TODO: implement setters
	 */
	SELF = wb.Property = wb.utilities.inherit( 'WbProperty', PARENT, {
		/**
		 * Will hold the data type object after it has been requested once.
		 * @type dataTypes.DataType
		 */
		_dataType: null,

		/**
		 * Returns the Property's data type.
		 *
		 * @since 0.4
		 *
		 * @return dataTypes.DataType
		 */
		getDataType: function() {
			if( !this._dataType ) {
				var typeId = this._data.datatype;

				if( !typeId ) { // shouldn't really happen!
					throw new Error( 'No data type specified for this Property' );
				}
				this._dataType = dt.getDataType( typeId );

				if( !this._dataType ) {
					throw new Error( 'The Property\'s data type "' + typeId + '" is unknown' );
				}
			}
			return this._dataType;
		},

		/**
		 * @see wb.Entity.equals
		 */
		equals: function( entity ) {
			if(
				entity instanceof SELF &&
				this._data.datatype !== entity.getDataType().getId()
			) {
				return false;
			}
			return PARENT.prototype.equals.call( this, entity );
		},

		/**
		 * @see wb.Entity.toMap
		 */
		toMap: function() {
			var map = PARENT.prototype.toMap.call( this );
			map.datatype = this.getDataType();
			return map;
		}
	} );


	/**
	 * @see wb.Entity.TYPE
	 */
	SELF.TYPE = 'property';

}( wikibase, dataTypes ) );
