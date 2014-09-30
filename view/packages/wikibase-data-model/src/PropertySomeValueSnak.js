/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, util ) {
'use strict';

var PARENT = wb.datamodel.Snak;

/**
 * @constructor
 * @extends wikibase.datamodel.Snak
 * @since 0.3
 *
 * @param {string} propertyId
 */
var SELF
	= wb.datamodel.PropertySomeValueSnak
	= util.inherit( 'WbDataModelPropertySomeValueSnak', PARENT, {} );

/**
 * @see wikibase.datamodel.Snak.TYPE
 */
SELF.TYPE = 'somevalue';

}( wikibase, util ) );
