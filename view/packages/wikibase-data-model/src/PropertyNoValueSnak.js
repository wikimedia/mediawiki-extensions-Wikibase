/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
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
	= wb.datamodel.PropertyNoValueSnak
	= util.inherit( 'WbDataModelPropertyNoValueSnak', PARENT, {} );

/**
 * @see wikibase.datamodel.Snak.TYPE
 */
SELF.TYPE = 'novalue';

}( wikibase, util ) );
