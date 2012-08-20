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
 * Represents a Wikibase PropertyNoValueSnak in JavaScript.
 * @constructor
 * @extends wb.Snak
 * @since 0.2
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#PropertyNoValueSnak
 *
 * @param {Number} propertyId
 */
wb.PropertyNoValueSnak = wb.utilities.inherit( PARENT, constructor, {
	/**
	 * @see wb.Snak.TYPE
	 */
	TYPE: 'novalue'
} );

}( mediaWiki, wikibase, jQuery ) );