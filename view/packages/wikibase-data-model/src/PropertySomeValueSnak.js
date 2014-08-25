/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, util ) {
'use strict';

var PARENT = wb.datamodel.Snak;

/**
 * Represents a Wikibase PropertySomeValueSnak.
 * @constructor
 * @extends wb.datamodel.Snak
 * @since 0.3
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
