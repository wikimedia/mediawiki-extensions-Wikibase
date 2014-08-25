/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util ) {
	'use strict';

	var PARENT = wb.datamodel.Entity;

	/**
	 * Represents a Wikibase Property.
	 *
	 * @constructor
	 * @extends wb.datamodel.Entity
	 * @since 0.3
	 *
	 * @param {Object} data
	 *
	 * TODO: implement setters
	 */
	var SELF = wb.datamodel.Property = util.inherit( 'WbProperty', PARENT, {

		/**
		 * Returns the Property's data type's identifier.
		 *
		 * @return string
		 */
		getDataType: function() {
			return this._data.datatype;
		},

		/**
		 * @see wb.datamodel.Entity.toMap
		 */
		toMap: function() {
			var map = PARENT.prototype.toMap.call( this );
			map.datatype = this.getDataType();
			return map;
		}
	} );


	/**
	 * @see wb.datamodel.Entity.TYPE
	 */
	SELF.TYPE = 'property';

}( wikibase, util ) );
