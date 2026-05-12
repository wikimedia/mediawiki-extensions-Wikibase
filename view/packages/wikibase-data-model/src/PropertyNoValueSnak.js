( function( util ) {
'use strict';

var PARENT = require( './Snak.js' );

/**
 * Snak explicitly occupying no value.
 * @class PropertyNoValueSnak
 * @extends Snak
 * @since 0.3
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 *
 * @constructor
 *
 * @param {string} propertyId
 * @param {string|null} [hash=null]
 */
var SELF = util.inherit( 'WbDataModelPropertyNoValueSnak', PARENT, {} );

/**
 * @inheritdoc
 * @property {string} [TYPE='novalue']
 * @static
 */
SELF.TYPE = 'novalue';

module.exports = SELF;

}( util ) );
