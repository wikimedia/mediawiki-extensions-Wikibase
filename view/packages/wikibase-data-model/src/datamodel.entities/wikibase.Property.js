/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util ) {
	'use strict';

	var PARENT = wb.Entity;

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
	var SELF = wb.Property = util.inherit( 'WbProperty', PARENT, {

		/**
		 * Returns the Property's data type's identifier.
		 *
		 * @since 0.4
		 *
		 * @return string
		 */
		getDataType: function() {
			return this._data.datatype;
		},

		/**
		 * @see wb.Entity.equals
		 */
		equals: function( entity ) {
			if(
				entity instanceof SELF
				&& this.getDataType() !== entity.getDataType()
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

}( wikibase, util ) );
