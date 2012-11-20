/**
 * QUnit tests for wikibase.Claim
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.3
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

( function( wb,$, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.snak.js', QUnit.newMwEnvironment() );

	QUnit.test( 'constructor', function( assert ) {
		var snakTypes = [
			wb.PropertyNoValueSnak,
			wb.PropertySomeValueSnak,
			wb.PropertyValueSnak
		];

		$.each( snakTypes, function( i, snakType ) {
			var snak = new snakType( 42 );

			assert.strictEqual(
				snak.getPropertyId(),
				42,
				'Property id was set correctly'
			);

			assert.strictEqual(
				snak.TYPE,
				snakType.prototype.TYPE,
				'Snak type was set correctly'
			);
		} );

	} );

}( wikibase, jQuery, QUnit ) );
