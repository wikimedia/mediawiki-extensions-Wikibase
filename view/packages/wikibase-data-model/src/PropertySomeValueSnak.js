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
 * @extends wikibase.datamodel.Snak
 * @since 0.3
 *
 * @param {string} propertyId
 */
var SELF = wb.datamodel.PropertySomeValueSnak = util.inherit( 'WbPropertySomeValueSnak', PARENT, {} );

/**
 * @see wikibase.datamodel.Snak.TYPE
 */
SELF.TYPE = 'somevalue';

}( wikibase, util ) );
