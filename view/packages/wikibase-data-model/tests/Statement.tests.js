/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, dv, $, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.Statement', QUnit.newWbEnvironment() );

	QUnit.test( 'Rank evaluation on instantiation', function( assert ) {
		var statement = new wb.datamodel.Statement(
			new wb.datamodel.PropertyValueSnak( 'P1', new dv.StringValue( 'string1' ) )
		);

		assert.equal(
			statement.getRank(),
			wb.datamodel.Statement.RANK.NORMAL,
			'Assigning \'normal\' rank by default.'
		);

		statement = new wb.datamodel.Statement(
			new wb.datamodel.PropertyValueSnak( 'P1', new dv.StringValue( 'string1' ) ),
			null,
			null,
			wb.datamodel.Statement.RANK.DEPRECATED
		);

		assert.equal(
			statement.getRank(),
			wb.datamodel.Statement.RANK.DEPRECATED,
			'Instantiated statement object with \'deprecated\' rank.'
		);
	} );

	QUnit.test( 'setRank() & getRank()', function( assert ) {
		var statement = new wb.datamodel.Statement( new wb.datamodel.PropertyNoValueSnak( 'P1' ) );

		statement.setRank( wb.datamodel.Statement.RANK.PREFERRED );

		assert.equal(
			statement.getRank(),
			wb.datamodel.Statement.RANK.PREFERRED,
			'Assigned \'preferred\' rank.'
		);

		statement.setRank( wb.datamodel.Statement.RANK.DEPRECATED );

		assert.equal(
			statement.getRank(),
			wb.datamodel.Statement.RANK.DEPRECATED,
			'Assigned \'deprecated\' rank.'
		);

		statement.setRank( wb.datamodel.Statement.RANK.NORMAL );

		assert.equal(
			statement.getRank(),
			wb.datamodel.Statement.RANK.NORMAL,
			'Assigned \'normal\' rank.'
		);
	} );

	QUnit.test( 'toJSON', function( assert ) {
		var statement = new wb.datamodel.Statement( new wb.datamodel.PropertyNoValueSnak( 'P42' ) );

		assert.ok(
			statement.equals( wb.datamodel.Claim.newFromJSON( statement.toJSON() ) ),
			'Exported simple statement to JSON.'
		);

		statement = new wb.datamodel.Statement(
			new wb.datamodel.PropertyValueSnak( 'P23', new dv.StringValue( '~=[,,_,,]:3' ) ),
			new wb.datamodel.SnakList(
				[
					new wb.datamodel.PropertyNoValueSnak( 'P9001' ),
					new wb.datamodel.PropertySomeValueSnak( 'P42' )
				]
			),
			[
				new wb.datamodel.Reference(
					new wb.datamodel.SnakList(
						[
							new wb.datamodel.PropertyValueSnak( 'P3', new dv.StringValue( 'string' ) ),
							new wb.datamodel.PropertySomeValueSnak( 'P245' )
						]
					)
				),
				new wb.datamodel.Reference(
					new wb.datamodel.SnakList(
						[
							new wb.datamodel.PropertyValueSnak( 'P856', new dv.StringValue( 'another string' ) ),
							new wb.datamodel.PropertySomeValueSnak( 'P97' )
						]
					)
				)
			],
			wb.datamodel.Statement.RANK.PREFERRED
		);

		assert.ok(
			statement.equals( wb.datamodel.Claim.newFromJSON( statement.toJSON() ) ),
			'Exported complex statement to JSON.'
		);

	} );

	QUnit.test( 'equals()', function( assert ) {
		var statements = [
			new wb.datamodel.Statement( new wb.datamodel.PropertyValueSnak( 'P42', new dv.StringValue( 'string' ) ) ),
			new wb.datamodel.Statement(
				new wb.datamodel.PropertyValueSnak( 'P42', new dv.StringValue( 'string' ) ),
				new wb.datamodel.SnakList(
					[
						new wb.datamodel.PropertyValueSnak( 'P2', new dv.StringValue( 'some string' ) ),
						new wb.datamodel.PropertySomeValueSnak( 'P9001' )
					]
				),
				[
					new wb.datamodel.Reference(
						new wb.datamodel.SnakList(
							[
								new wb.datamodel.PropertyValueSnak( 'P3', new dv.StringValue( 'string' ) ),
								new wb.datamodel.PropertySomeValueSnak( 'P245' )
							]
						)
					),
					new wb.datamodel.Reference(
						new wb.datamodel.SnakList(
							[
								new wb.datamodel.PropertyValueSnak( 'P856', new dv.StringValue( 'another string' ) ),
								new wb.datamodel.PropertySomeValueSnak( 'P97' )
							]
						)
					)
				],
				wb.datamodel.Statement.RANK.PREFERRED
			),
			new wb.datamodel.Statement( new wb.datamodel.PropertyValueSnak( 'P41', new dv.StringValue( 'string' ) ) ),
			new wb.datamodel.Statement(
				new wb.datamodel.PropertyValueSnak( 'P42', new dv.StringValue( 'string' ) ),
				new wb.datamodel.SnakList(
					[
						new wb.datamodel.PropertyValueSnak( 'P2', new dv.StringValue( 'some string' ) ),
						new wb.datamodel.PropertySomeValueSnak( 'P9001' )
					]
				)
			),
			new wb.datamodel.Statement(
				new wb.datamodel.PropertyValueSnak( 'P42', new dv.StringValue( 'string' ) ),
				new wb.datamodel.SnakList(
					[
						new wb.datamodel.PropertyValueSnak( 'P2', new dv.StringValue( 'some string' ) ),
						new wb.datamodel.PropertySomeValueSnak( 'P9001' )
					]
				),
				[
					new wb.datamodel.Reference(
						new wb.datamodel.SnakList(
							[
								new wb.datamodel.PropertyValueSnak( 'P3', new dv.StringValue( 'string' ) ),
								new wb.datamodel.PropertySomeValueSnak( 'P245' )
							]
						)
					),
					new wb.datamodel.Reference(
						new wb.datamodel.SnakList(
							[
								new wb.datamodel.PropertyValueSnak( 'P123', new dv.StringValue( 'another string' ) ),
								new wb.datamodel.PropertySomeValueSnak( 'P97' )
							]
						)
					)
				],
				wb.datamodel.Statement.RANK.PREFERRED
			)
		];

		// Compare statements:
		$.each( statements, function( i, statement ) {
			var clonedStatement = wb.datamodel.Claim.newFromJSON( statement.toJSON() );

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
		var claim = new wb.datamodel.Claim( new wb.datamodel.PropertyValueSnak( 'P42', new dv.StringValue( 'string' ) ) ),
			statement = new wb.datamodel.Statement(
				new wb.datamodel.PropertyValueSnak( 'P42', new dv.StringValue( 'string' ) )
			);

		assert.ok(
			!statement.equals( claim ),
			'Statement does not equal claim that received the same initialization parameters.'
		);

	} );

}( wikibase, dataValues, jQuery, QUnit ) );
