/**
 * QUnit tests for wikibase.Claim
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.3
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, dv, $, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.Claim', QUnit.newWbEnvironment() );

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

	QUnit.test( 'equals()', function( assert ) {
		var claims_equal = {
			a: [
				new wb.Claim( new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ) ),
				new wb.Claim( new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ) )
			],
			b: [
				new wb.Claim(
					new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ),
					new wb.SnakList(
						[
							new wb.PropertyValueSnak( 2, new dv.StringValue( 'some string' ) ),
							new wb.PropertySomeValueSnak( 9001 )
						]
					)
				),
				new wb.Claim(
					new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ),
					new wb.SnakList(
						[
							new wb.PropertyValueSnak( 2, new dv.StringValue( 'some string' ) ),
							new wb.PropertySomeValueSnak( 9001 )
						]
					)
				)
			],
			c: [
				new wb.Claim( new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ) ),
				new wb.Statement( new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ) )
			]
		},
		claims_unequal = {
			a: [
				new wb.Claim( new wb.PropertyValueSnak( 42, new dv.StringValue( 'other string' ) ) ),
				new wb.Claim( new wb.PropertySomeValueSnak( 9001 ) ),
				new wb.Claim(
					new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ),
					new wb.SnakList(
						[
							new wb.PropertyValueSnak( 2, new dv.StringValue( 'some string' ) ),
							new wb.PropertySomeValueSnak( 9001 )
						]
					)
				)
			],
			b: [
				new wb.Claim( new wb.PropertyValueSnak( 42, new dv.StringValue( 'other string' ) ) ),
				new wb.Claim( new wb.PropertySomeValueSnak( 9001 ) ),
				new wb.Claim(
					new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ),
					new wb.SnakList(
						[
							new wb.PropertyValueSnak( 43, new dv.StringValue( 'some string' ) ),
							new wb.PropertySomeValueSnak( 9001 )
						]
					)
				)
			]
		};

		// Compare equal claims:
		$.each( claims_equal, function( key, claims ) {
			assert.ok(
				claims[0].equals( claims[1] ),
				'Claims "' + key + '" are equal.'
			);
		} );

		// Compare "unequal" references to the "equal" references with the same key:
		$.each( claims_unequal, function( key, claims ) {
			$.each( claims, function( i, claim ) {
				assert.ok(
					!claim.equals( claims_equal[key][0] ),
					'Unequal claim "' + key + '[' + i + ']" is recognized being unequal.'
				);
			} )
		} );
	} );

	QUnit.test( 'toJSON()', function( assert ) {
		var claim = new wb.Claim( new wb.PropertyValueSnak( 42, new dv.StringValue( '~=[,,_,,]:3' ) ) );

		assert.ok(
			claim.equals( wb.Claim.newFromJSON( claim.toJSON() ) ),
			'Exported simple claim to JSON.'
		);

		claim = new wb.Claim(
			new wb.PropertyNoValueSnak( 42 ),
			new wb.SnakList(
				[
					new wb.PropertyNoValueSnak( 9001 ),
					new wb.PropertySomeValueSnak( 42 ),
					new wb.PropertyValueSnak( 23, new dv.StringValue( '~=[,,_,,]:3' ) )
				]
			)
		);

		assert.ok(
			claim.equals( wb.Claim.newFromJSON( claim.toJSON() ) ),
			'Exported complex claim to JSON.'
		);
	} );

}( wikibase, dataValues, jQuery, QUnit ) );
