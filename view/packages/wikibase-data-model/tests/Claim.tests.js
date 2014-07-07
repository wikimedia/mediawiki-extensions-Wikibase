/**
 * QUnit tests for wikibase.Claim
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.3
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
				mainSnak: new wb.datamodel.PropertyNoValueSnak( 'p42' ),
				qualifiers: new wb.datamodel.SnakList()
			}, {
				mainSnak: new wb.datamodel.PropertySomeValueSnak( 'p9001' ),
				qualifiers: new wb.datamodel.SnakList()
			}, {
				mainSnak: new wb.datamodel.PropertyValueSnak( 'p23', new dv.StringValue( '~=[,,_,,]:3' ) ),
				qualifiers: new wb.datamodel.SnakList()
			}
		];

		$.each( argumentLists, function( i, constructorArguments ) {
			var claim = new wb.datamodel.Claim(
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
		var claim = new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'p42' ),
				new wb.datamodel.SnakList()
			),
			snaks = [
				new wb.datamodel.PropertyNoValueSnak( 'p9001' ),
				new wb.datamodel.PropertySomeValueSnak( 'p42' ),
				new wb.datamodel.PropertyValueSnak( 'p23', new dv.StringValue( '~=[,,_,,]:3' ) )
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
		var claim = new wb.datamodel.Claim( new wb.datamodel.PropertyValueSnak( 'p42', new dv.StringValue( '~=[,,_,,]:3' ) ) );

		assert.ok(
			claim.equals( wb.datamodel.Claim.newFromJSON( claim.toJSON() ) ),
			'Exported simple claim to JSON.'
		);

		claim = new wb.datamodel.Claim(
			new wb.datamodel.PropertyNoValueSnak( 'p42' ),
			new wb.datamodel.SnakList(
				[
					new wb.datamodel.PropertyNoValueSnak( 'p9001' ),
					new wb.datamodel.PropertySomeValueSnak( 'p42' ),
					new wb.datamodel.PropertyValueSnak( 'p23', new dv.StringValue( '~=[,,_,,]:3' ) )
				]
			)
		);

		assert.ok(
			claim.equals( wb.datamodel.Claim.newFromJSON( claim.toJSON() ) ),
			'Exported complex claim to JSON.'
		);
	} );

	QUnit.test( 'equals()', function( assert ) {
		var claims = [
			new wb.datamodel.Claim( new wb.datamodel.PropertyValueSnak( 'p42', new dv.StringValue( 'string' ) ) ),
			new wb.datamodel.Claim(
				new wb.datamodel.PropertyValueSnak( 'p42', new dv.StringValue( 'string' ) ),
				new wb.datamodel.SnakList(
					[
						new wb.datamodel.PropertyValueSnak( 'p2', new dv.StringValue( 'some string' ) ),
						new wb.datamodel.PropertySomeValueSnak( 'p9001' )
					]
				)
			),
			new wb.datamodel.Claim( new wb.datamodel.PropertyValueSnak( 'p42', new dv.StringValue( 'other string' ) ) ),
			new wb.datamodel.Claim( new wb.datamodel.PropertySomeValueSnak( 'p9001' ) ),
			new wb.datamodel.Claim(
				new wb.datamodel.PropertyValueSnak( 'p42', new dv.StringValue( 'string' ) ),
				new wb.datamodel.SnakList(
					[
						new wb.datamodel.PropertyValueSnak( 'p43', new dv.StringValue( 'some string' ) ),
						new wb.datamodel.PropertySomeValueSnak( 'p9001' )
					]
				)
			)
		];

		// Compare claims:
		$.each( claims, function( i, claim ) {
			var clonedClaim = wb.datamodel.Claim.newFromJSON( claim.toJSON() );

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
		var claim = new wb.datamodel.Claim( new wb.datamodel.PropertyValueSnak( 'p42', new dv.StringValue( 'string' ) ) ),
			statement = new wb.datamodel.Statement(
				new wb.datamodel.PropertyValueSnak( 'p42', new dv.StringValue( 'string' ) )
			);

		assert.ok(
			claim.equals( statement ),
			'Claim equals statement that received the same initialization parameters.'
		);

	} );

}( wikibase, dataValues, jQuery, QUnit ) );
