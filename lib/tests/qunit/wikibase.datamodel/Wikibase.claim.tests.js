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

( function( wb, dv, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.claim.js', QUnit.newMwEnvironment() );

	QUnit.test( 'constructor', function( assert ) {
		var argumentLists = [
			{
				mainSnak: new wb.PropertyNoValueSnak( 42 ),
				qualifiers: []
			},
			{
				mainSnak: new wb.PropertySomeValueSnak( 9001 ),
				qualifiers: []
			},
			{
				mainSnak: new wb.PropertyValueSnak( 23, new dv.StringValue( '~=[,,_,,]:3' ) ),
				qualifiers: []
			}
		];

		$.each( argumentLists, function( i, constructorArguments ) {
			var claim = new wb.Claim(
				constructorArguments.mainSnak,
				constructorArguments.qualifiers
			);

			// TODO: replace with comparison function implemented in snak
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

			// TODO: test qualifiers
		} );

	} );

	QUnit.test( 'setMainSnak and getMainSnak', function( assert ) {
		var claim = new wb.Claim( new wb.PropertyNoValueSnak( 42 ), [] ),
			snaks = [
				new wb.PropertyNoValueSnak( 9001 ),
				new wb.PropertySomeValueSnak( 42 ),
				new wb.PropertyValueSnak( 23, new dv.StringValue( '~=[,,_,,]:3' ) )
			];

		$.each( snaks, function( i, snak ) {
			claim.setMainSnak( snak );

			// TODO: replace with comparison function implemented in snak
			assert.strictEqual(
				claim.getMainSnak().getPropertyId(),
				snak.getPropertyId(),
				'Main snak property id is correct'
			);

			assert.strictEqual(
				claim.getMainSnak().TYPE,
				snak.TYPE,
				'Main snak type is correct'
			);
		} );
	} );

}( wikibase, dataValues, jQuery, QUnit ) );
