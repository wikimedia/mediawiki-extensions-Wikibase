/**
 * @file
 * @ingroup Wikibase
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, $, undefined ) {
'use strict';

var PARENT = wb.Snak;

/**
 * Represents a Wikibase PropertyNoValueSnak in JavaScript.
 * @constructor
 * @extends wb.Snak
 * @since 0.2
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#PropertyNoValueSnak
 *
 * @param {Number} propertyId
 */
wb.PropertyNoValueSnak = wb.utilities.inherit( PARENT, {
	/**
	 * @see wb.Snak.TYPE
	 */
	TYPE: 'novalue'
} );

}( wikibase, jQuery ) );
