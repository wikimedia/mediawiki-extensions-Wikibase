( function( util ) {
'use strict';

var PARENT = require( './Snak.js' );

/**
 * Snak occupying some value.
 * @class PropertySomeValueSnak
 * @extends Snak
 * @since 0.3
 * @license GPL-2.0+
 * @author Daniel Werner
 *
 * @constructor
 *
 * @param {string} propertyId
 * @param {string|null} [hash=null]
 */
var SELF = util.inherit( 'WbDataModelPropertySomeValueSnak', PARENT, {} );

/**
 * @inheritdoc
 * @property {string} [TYPE='somevalue']
 * @static
 */
SELF.TYPE = 'somevalue';

module.exports = SELF;

}( util ) );
