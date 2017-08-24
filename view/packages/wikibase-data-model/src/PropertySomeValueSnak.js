( function( wb, util ) {
'use strict';

var PARENT = wb.datamodel.Snak;

/**
 * Snak occupying some value.
 * @class wikibase.datamodel.PropertySomeValueSnak
 * @extends wikibase.datamodel.Snak
 * @since 0.3
 * @license GPL-2.0+
 * @author Daniel Werner
 *
 * @constructor
 *
 * @param {string} propertyId
 * @param {string|null} [hash=null]
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
