( function( wb, util ) {
'use strict';

var PARENT = wb.datamodel.Snak;

/**
 * Snak explicitly occupying no value.
 * @class wikibase.datamodel.PropertyNoValueSnak
 * @extends wikibase.datamodel.Snak
 * @since 0.3
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 *
 * @constructor
 *
 * @param {string} propertyId
 * @param {string|null} [hash=null]
 */
var SELF
	= wb.datamodel.PropertyNoValueSnak
	= util.inherit( 'WbDataModelPropertyNoValueSnak', PARENT, {} );

/**
 * @inheritdoc
 * @property {string} [TYPE='novalue']
 * @static
 */
SELF.TYPE = 'novalue';

}( wikibase, util ) );
