/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb ) {
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
 * @param {dataValues.Value} value
 */
var SELF = wb.PropertySomeValueSnak = wb.utilities.inherit( 'WbPropertySomeValueSnak', PARENT, {} );

/**
 * @see wb.Snak.TYPE
 * @type String
 */
SELF.TYPE = 'somevalue';

}( wikibase ) );
