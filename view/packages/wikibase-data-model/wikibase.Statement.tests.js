/**
 * QUnit tests for wikibase.Statement
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.4
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, dv, $, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.Statement', QUnit.newWbEnvironment() );

	QUnit.test( 'toJSON', function( assert ) {
		var statement = new wb.Statement( new wb.PropertyNoValueSnak( 'p42' ) );

		assert.ok(
			statement.equals( wb.Claim.newFromJSON( statement.toJSON() ) ),
			'Exported simple statement to JSON.'
		);

		statement = new wb.Statement(
			new wb.PropertyValueSnak( 'p23', new dv.StringValue( '~=[,,_,,]:3' ) ),
			new wb.SnakList(
				[
					new wb.PropertyNoValueSnak( 'p9001' ),
					new wb.PropertySomeValueSnak( 'p42' )
				]
			),
			[
				new wb.Reference(
					new wb.SnakList(
						[
							new wb.PropertyValueSnak( 'p3', new dv.StringValue( 'string' ) ),
							new wb.PropertySomeValueSnak( 'p245' )
						]
					)
				),
				new wb.Reference(
					new wb.SnakList(
						[
							new wb.PropertyValueSnak( 'p856', new dv.StringValue( 'another string' ) ),
							new wb.PropertySomeValueSnak( 'p97' )
						]
					)
				)
			],
			wb.Statement.RANK.PREFERRED
		);

		assert.ok(
			statement.equals( wb.Claim.newFromJSON( statement.toJSON() ) ),
			'Exported complex statement to JSON.'
		);

	} );

	QUnit.test( 'equals()', function( assert ) {
		var statements = [
			new wb.Statement( new wb.PropertyValueSnak( 'p42', new dv.StringValue( 'string' ) ) ),
			new wb.Statement(
				new wb.PropertyValueSnak( 'p42', new dv.StringValue( 'string' ) ),
				new wb.SnakList(
					[
						new wb.PropertyValueSnak( 'p2', new dv.StringValue( 'some string' ) ),
						new wb.PropertySomeValueSnak( 'p9001' )
					]
				),
				[
					new wb.Reference(
						new wb.SnakList(
							[
								new wb.PropertyValueSnak( 'p3', new dv.StringValue( 'string' ) ),
								new wb.PropertySomeValueSnak( 'p245' )
							]
						)
					),
					new wb.Reference(
						new wb.SnakList(
							[
								new wb.PropertyValueSnak( 'p856', new dv.StringValue( 'another string' ) ),
								new wb.PropertySomeValueSnak( 'p97' )
							]
						)
					)
				],
				wb.Statement.RANK.PREFERRED
			),
			new wb.Statement( new wb.PropertyValueSnak( 41, new dv.StringValue( 'string' ) ) ),
			new wb.Statement(
				new wb.PropertyValueSnak( 'p42', new dv.StringValue( 'string' ) ),
				new wb.SnakList(
					[
						new wb.PropertyValueSnak( 2, new dv.StringValue( 'some string' ) ),
						new wb.PropertySomeValueSnak( 'p9001' )
					]
				)
			),
			new wb.Statement(
				new wb.PropertyValueSnak( 'p42', new dv.StringValue( 'string' ) ),
				new wb.SnakList(
					[
						new wb.PropertyValueSnak( 'p2', new dv.StringValue( 'some string' ) ),
						new wb.PropertySomeValueSnak( 'p9001' )
					]
				),
				[
					new wb.Reference(
						new wb.SnakList(
							[
								new wb.PropertyValueSnak( 'p3', new dv.StringValue( 'string' ) ),
								new wb.PropertySomeValueSnak( 'p245' )
							]
						)
					),
					new wb.Reference(
						new wb.SnakList(
							[
								new wb.PropertyValueSnak( 'p123', new dv.StringValue( 'another string' ) ),
								new wb.PropertySomeValueSnak( 'p97' )
							]
						)
					)
				],
				wb.Statement.RANK.PREFERRED
			)
		];

		// Compare statements:
		$.each( statements, function( i, statement ) {
			var clonedStatement = wb.Claim.newFromJSON( statement.toJSON() );

			// Check if "cloned" statement is equal:
			assert.ok(
				statement.equals( clonedStatement ),
				'Verified statement "' + i + '" on equality.'
			);

			// Compare to all other statements:
			$.each( statements, function( j, otherStatement ) {
				if ( j !== i ) {
					assert.ok(
						!statement.equals( otherStatement ),
						'Statement "' + i + '" is not equal to statement "'+ j + '".'
					);
				}
			} );

		} );

		// Compare claim to statement:
		var claim = new wb.Claim( new wb.PropertyValueSnak( 'p42', new dv.StringValue( 'string' ) ) ),
			statement = new wb.Statement(
				new wb.PropertyValueSnak( 'p42', new dv.StringValue( 'string' ) )
			);

		assert.ok(
			!statement.equals( claim ),
			'Statement does not equal claim that received the same initialization parameters.'
		);

	} );

}( wikibase, dataValues, jQuery, QUnit ) );
