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

	QUnit.test( 'equals()', function( assert ) {
		var claims = [
			new wb.Claim( new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ) ),
			new wb.Claim(
				new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ),
				new wb.SnakList(
					[
						new wb.PropertyValueSnak( 2, new dv.StringValue( 'some string' ) ),
						new wb.PropertySomeValueSnak( 9001 )
					]
				)
			),
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
		];

		// Compare claims:
		$.each( claims, function( i, claim ) {
			var clonedClaim = wb.Claim.newFromJSON( claim.toJSON() );

			// Check if "cloned" claim is equal:
			assert.ok(
				claim.equals( clonedClaim ),
				'Verified claim "' + i + '" on equality.'
			);

			// Compare to all other claims:
			$.each( claims, function( j, otherClaim ) {
				if ( j !== i ) {
					assert.ok(
						!claim.equals( otherClaim ),
						'Claim "' + i + '" is not equal to claim "'+ j + '".'
					);
				}
			} );

		} );

		// Compare claim to statement:
		var claim = new wb.Claim( new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ) ),
			statement = new wb.Statement(
				new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) )
			);

		assert.ok(
			claim.equals( statement ),
			'Claim equals statement that received the same initialization parameters.'
		);

	} );

}( wikibase, dataValues, jQuery, QUnit ) );
