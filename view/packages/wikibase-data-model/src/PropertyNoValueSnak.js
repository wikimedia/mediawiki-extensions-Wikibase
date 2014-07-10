/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util ) {
'use strict';

var PARENT = wb.datamodel.Snak;

/**
 * Represents a Wikibase PropertyNoValueSnak in JavaScript.
 * @constructor
 * @extends wb.datamodel.Snak
 * @since 0.2
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#PropertyNoValueSnak
 *
 * @param {Number} propertyId
 */
var SELF = wb.datamodel.PropertyNoValueSnak = util.inherit( 'WbPropertyNoValueSnak', PARENT, {} );

/**
 * @see wb.datamodel.Snak.TYPE
 * @type String
 */
SELF.TYPE = 'novalue';

}( wikibase, util ) );
