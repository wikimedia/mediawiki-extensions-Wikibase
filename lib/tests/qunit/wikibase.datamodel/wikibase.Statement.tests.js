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

	QUnit.module( 'wikibase.datamodel.statement', QUnit.newWbEnvironment() );

	QUnit.test( 'equals()', function( assert ) {
		var statements_equal = {
			a: [
				new wb.Statement( new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ) ),
				new wb.Statement( new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ) )
			],
			b: [
				new wb.Statement(
					new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ),
					new wb.SnakList(
						[
							new wb.PropertyValueSnak( 2, new dv.StringValue( 'some string' ) ),
							new wb.PropertySomeValueSnak( 9001 )
						]
					),
					[
						new wb.Reference(
							new wb.SnakList(
								[
									new wb.PropertyValueSnak( 3, new dv.StringValue( 'string' ) ),
									new wb.PropertySomeValueSnak( 245 )
								]
							)
						),
						new wb.Reference(
							new wb.SnakList(
								[
									new wb.PropertyValueSnak( 856, new dv.StringValue( 'another string' ) ),
									new wb.PropertySomeValueSnak( 97 )
								]
							)
						)
					],
					wb.Statement.RANK.PREFERRED
				),
				new wb.Statement(
					new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ),
					new wb.SnakList(
						[
							new wb.PropertyValueSnak( 2, new dv.StringValue( 'some string' ) ),
							new wb.PropertySomeValueSnak( 9001 )
						]
					),
					[
						new wb.Reference(
							new wb.SnakList(
								[
									new wb.PropertyValueSnak( 3, new dv.StringValue( 'string' ) ),
									new wb.PropertySomeValueSnak( 245 )
								]
							)
						),
						new wb.Reference(
							new wb.SnakList(
								[
									new wb.PropertyValueSnak( 856, new dv.StringValue( 'another string' ) ),
									new wb.PropertySomeValueSnak( 97 )
								]
							)
						)
					],
					wb.Statement.RANK.PREFERRED
				)
			]
		},
		statements_unequal = {
			a: [
				new wb.Statement( new wb.PropertyValueSnak( 41, new dv.StringValue( 'string' ) ) ),
				new wb.Claim( new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ) ),
				new wb.Statement(
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
				new wb.Statement( new wb.PropertyValueSnak( 41, new dv.StringValue( 'string' ) ) ),
				new wb.Statement(
					new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ),
					new wb.SnakList(
						[
							new wb.PropertyValueSnak( 2, new dv.StringValue( 'some string' ) ),
							new wb.PropertySomeValueSnak( 9001 )
						]
					)
				),
				new wb.Statement(
					new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ),
					new wb.SnakList(
						[
							new wb.PropertyValueSnak( 2, new dv.StringValue( 'some string' ) ),
							new wb.PropertySomeValueSnak( 9001 )
						]
					),
					[
						new wb.Reference(
							new wb.SnakList(
								[
									new wb.PropertyValueSnak( 3, new dv.StringValue( 'string' ) ),
									new wb.PropertySomeValueSnak( 245 )
								]
							)
						),
						new wb.Reference(
							new wb.SnakList(
								[
									new wb.PropertyValueSnak( 123, new dv.StringValue( 'another string' ) ),
									new wb.PropertySomeValueSnak( 97 )
								]
							)
						)
					],
					wb.Statement.RANK.PREFERRED
				)
			]
		};

		// Compare equal statements:
		$.each( statements_equal, function( key, statements ) {
			assert.ok(
				statements[0].equals( statements[1] ),
				'Statements "' + key + '" are equal.'
			);
		} );

		// Compare "unequal" references to the "equal" references with the same key:
		$.each( statements_unequal, function( key, statements ) {
			$.each( statements, function( i, statement ) {
				assert.ok(
					!statements_equal[key][0].equals( statement ),
					'Unequal statement "' + key + '[' + i + ']" is recognized being unequal.'
				);
			} )
		} );
	} );

	QUnit.test( 'toJSON', function( assert ) {
		var statement = new wb.Statement( new wb.PropertyNoValueSnak( 42 ) );

		assert.ok(
			statement.equals( wb.Claim.newFromJSON( statement.toJSON() ) ),
			'Exported simple statement to JSON.'
		);

		statement = new wb.Statement(
			new wb.PropertyValueSnak( 23, new dv.StringValue( '~=[,,_,,]:3' ) ),
			new wb.SnakList(
				[
					new wb.PropertyNoValueSnak( 9001 ),
					new wb.PropertySomeValueSnak( 42 )
				]
			),
			[
				new wb.Reference(
					new wb.SnakList(
						[
							new wb.PropertyValueSnak( 3, new dv.StringValue( 'string' ) ),
							new wb.PropertySomeValueSnak( 245 )
						]
					)
				),
				new wb.Reference(
					new wb.SnakList(
						[
							new wb.PropertyValueSnak( 856, new dv.StringValue( 'another string' ) ),
							new wb.PropertySomeValueSnak( 97 )
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

}( wikibase, dataValues, jQuery, QUnit ) );
