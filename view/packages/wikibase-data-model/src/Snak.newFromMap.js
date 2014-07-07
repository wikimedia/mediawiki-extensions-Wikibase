/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb ) {
'use strict';

/**
 * Creates a new Snak Object from a given Object with certain keys and values, what an actual Snak
 * would return when calling its toMap().
 *
 * @since 0.4
 *
 * @param {Object} map Requires at least 'snaktype' and 'property' fields.
 * @return wb.datamodel.Snak|null
 */
wb.datamodel.Snak.newFromMap = function( map ) {
	switch( map.snaktype ) {
		case 'value':
			return new wb.datamodel.PropertyValueSnak( map.property, map.datavalue );
		case 'novalue':
			return new wb.datamodel.PropertyNoValueSnak( map.property );
		case 'somevalue':
			return new wb.datamodel.PropertySomeValueSnak( map.property );
		default:
			return null;
	}
};

}( wikibase ) );
