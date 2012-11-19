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

	QUnit.module( 'wikibase.datamodel.claim.js', QUnit.newMwEnvironment() );

	QUnit.test( 'constructor', function( assert ) {
		var argumentLists = [
			{
				mainSnak: new wb.PropertyNoValueSnak( 42 ),
				qualifiers: []
			}
		];

		$.each( argumentLists, function( i, constructorArguments ) {
			var claim = new wb.Claim(
				constructorArguments.mainSnak,
				constructorArguments.qualifiers
			);

			assert.strictEqual(
				claim.getMainSnak().getPropertyId(),
				constructorArguments.mainSnak.getPropertyId(),
				'Main snak property id is correct'
			);

			assert.strictEqual(
				claim.getMainSnak().TYPE,
				constructorArguments.mainSnak.TYPE,
				'Main snak type is correct'
			);
		} );

	} );

}( wikibase, jQuery, QUnit ) );
