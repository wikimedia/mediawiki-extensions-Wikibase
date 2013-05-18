/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb ) {
'use strict';

var PARENT = wb.Snak,
	SELF;

/**
 * Represents a Wikibase PropertyNoValueSnak in JavaScript.
 * @constructor
 * @extends wb.Snak
 * @since 0.2
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#PropertyNoValueSnak
 *
 * @param {Number} propertyId
 */
SELF = wb.PropertyNoValueSnak = wb.utilities.inherit( 'WbPropertyNoValueSnak', PARENT, {} );

/**
 * @see wb.Snak.TYPE
 * @type String
 */
SELF.TYPE = 'novalue';

}( wikibase ) );
