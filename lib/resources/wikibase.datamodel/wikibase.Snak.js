/**
 * @file
 * @ingroup Wikibase
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, dv, $, undefined ) {
'use strict';

/**
 * Represents a Wikibase Snak in JavaScript.
 * @constructor
 * @abstract
 * @since 0.2
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#Snaks
 *
 * @param {Number} propertyId
 */
wb.Snak = function( propertyId ) {
	this._propertyId = propertyId;
};

wb.Snak.prototype = {
	/**
	 * String to identify this type of Snak
	 * @type String
	 */
	TYPE: null,

	/**
	 * @type Number
	 */
	_propertyId: null,

	/**
	 * Returns the ID of the property entity the snak relates to.
	 * @return Number
	 */
	getPropertyId: function() {
		return this._propertyId;
	}
};

/**
 * Creates a new Snak object from a given JSON structure.
 *
 * @param {String} json
 * @return {wb.Snak}
 */
wb.Snak.newFromJSON = function( json ) {
	// TODO: here we have to know what type the property has, but we don't!
	//       this is a very dirty hack which will only work as long as we only have data types which
	//       use the string data value type!
	var dataValue = new dv.StringValue( json.value );

	switch( json.snaktype ) {
		case 'value':
			return new wb.PropertyValueSnak( json.property, dataValue );
		case 'novalue':
			return new wb.PropertyNoValueSnak( json.property, dataValue );
		case 'somevalue':
			return new wb.PropertySomeValueSnak( json.property, dataValue );
		default:
			return null;
	}
};

}( wikibase, dataValues, jQuery ) );
