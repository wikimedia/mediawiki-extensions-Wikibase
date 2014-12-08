( function( wb, util ) {
'use strict';

var PARENT = wb.datamodel.Snak;

/**
 * Snak occupying some value.
 * @class wikibase.datamodel.PropertySomeValueSnak
 * @extends wikibase.datamodel.Snak
 * @since 0.3
 * @licence GNU GPL v2+
 * @author Daniel Werner
 *
 * @constructor
 *
 * @param {string} propertyId
 */
var SELF
	= wb.datamodel.PropertySomeValueSnak
	= util.inherit( 'WbDataModelPropertySomeValueSnak', PARENT, {} );

/**
 * @inheritdoc
 * @property {string} [TYPE='somevalue']
 * @static
 */
SELF.TYPE = 'somevalue';

}( wikibase, util ) );
