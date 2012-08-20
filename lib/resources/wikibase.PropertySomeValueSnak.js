/**
 * @file
 * @ingroup Wikibase
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $, undefined ) {
'use strict';

var PARENT = wb.Snak;

/**
 * Represents a Wikibase PropertySomeValueSnak in JavaScript.
 * @constructor
 * @extends wb.Snak
 * @since 0.2
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#PropertySomeValueSnak
 *
 * @param {Number} propertyId
 * @param {DataValues.Value} value
 */
wb.PropertySomeValueSnak = wb.utilities.inherit( PARENT, {
	TYPE: 'somevalue'
} );

}( mediaWiki, wikibase, jQuery ) );