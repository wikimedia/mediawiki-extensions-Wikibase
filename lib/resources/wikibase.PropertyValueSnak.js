/**
 * @file
 * @ingroup Wikibase
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $, undefined ) {
'use strict';

var PARENT = wb.Snak,
	constructor = function( propertyId, value ) {
		PARENT.call( this, propertyId );
		this._value = value;
	};

/**
 * Represents a Wikibase PropertyValueSnak in JavaScript.
 * @constructor
 * @extends wb.Snak
 * @since 0.2
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#PropertyValueSnak
 *
 * @param {Number} propertyId
 * @param {DataValues.Value} value
 */
wb.PropertyValueSnak = wb.utilities.inherit( PARENT, constructor, {
	/**
	 * @see wb.Snak.TYPE
	 */
	TYPE: 'value',

	/**
	 * @type DataValues.Value
	 */
	_value: null,

	/**
	 * Returns the Snaks data value.
	 *
	 * @return {DataValues.Value|_value}
	 */
	getValue: function() {
		return this._value;
	}
} );

}( mediaWiki, wikibase, jQuery ) );