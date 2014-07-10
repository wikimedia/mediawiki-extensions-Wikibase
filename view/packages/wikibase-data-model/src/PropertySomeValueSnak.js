/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, util ) {
'use strict';

var PARENT = wb.datamodel.Snak;

/**
 * Represents a Wikibase PropertySomeValueSnak in JavaScript.
 * @constructor
 * @extends wb.datamodel.Snak
 * @since 0.2
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#PropertySomeValueSnak
 *
 * @param {Number} propertyId
 * @param {dataValues.Value} value
 */
var SELF = wb.datamodel.PropertySomeValueSnak = util.inherit( 'WbPropertySomeValueSnak', PARENT, {} );

/**
 * @see wb.datamodel.Snak.TYPE
 * @type String
 */
SELF.TYPE = 'somevalue';

}( wikibase, util ) );
