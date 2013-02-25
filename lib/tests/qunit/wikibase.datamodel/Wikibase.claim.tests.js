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

( function( wb, dv, $, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.claim.js', QUnit.newMwEnvironment() );

	QUnit.test( 'constructor', function( assert ) {
		var argumentLists = [
			{
				mainSnak: new wb.PropertyNoValueSnak( 42 ),
				qualifiers: new wb.SnakList()
			}, {
				mainSnak: new wb.PropertySomeValueSnak( 9001 ),
				qualifiers: new wb.SnakList()
			}, {
				mainSnak: new wb.PropertyValueSnak( 23, new dv.StringValue( '~=[,,_,,]:3' ) ),
				qualifiers: new wb.SnakList()
			}
		];

		$.each( argumentLists, function( i, constructorArguments ) {
			var claim = new wb.Claim(
				constructorArguments.mainSnak,
				constructorArguments.qualifiers
			);

			assert.ok(
				claim.getMainSnak().equals( constructorArguments.mainSnak ),
				'Main snak is set correctly'
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
		var claim = new wb.Claim(
				new wb.PropertyNoValueSnak( 42 ),
				new wb.SnakList()
			),
			snaks = [
				new wb.PropertyNoValueSnak( 9001 ),
				new wb.PropertySomeValueSnak( 42 ),
				new wb.PropertyValueSnak( 23, new dv.StringValue( '~=[,,_,,]:3' ) )
			];

		$.each( snaks, function( i, snak ) {
			claim.setMainSnak( snak );

			assert.ok(
				claim.getMainSnak().equals( snak ),
				'Main snak is set correctly'
			);

			assert.strictEqual(
				claim.getMainSnak().TYPE,
				snak.TYPE,
				'Main snak type is correct'
			);
		} );
	} );

}( wikibase, dataValues, jQuery, QUnit ) );
