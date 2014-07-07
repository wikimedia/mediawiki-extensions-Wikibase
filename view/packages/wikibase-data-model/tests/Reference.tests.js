/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, dv, $, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.Reference', QUnit.newWbEnvironment() );

	var snakLists = [
		new wb.datamodel.SnakList( [
			new wb.datamodel.PropertyNoValueSnak( 'P9001' ),
			new wb.datamodel.PropertySomeValueSnak( 'P42' ),
			new wb.datamodel.PropertyValueSnak( 'P23', new dv.StringValue( '~=[,,_,,]:3' ) )
		] ),
		new wb.datamodel.SnakList( [] ),
		new wb.datamodel.SnakList( [ new wb.datamodel.PropertyNoValueSnak( 'P9001' ) ] )
	];

	QUnit.test( 'constructor', function( assert ) {
		$.each( snakLists, function( i, snakList ) {
			var reference = new wb.datamodel.Reference( snakList );

			assert.ok(
				reference.getSnaks().equals( snakList ),
				'Snaks were set correctly'
			);
		} );
	} );

	QUnit.test( 'setSnaks and getSnaks', function( assert ) {
		$.each( snakLists, function( i, snakList ) {
			var reference = new wb.datamodel.Reference( [] );

			reference.setSnaks( snakList );

			assert.ok(
				reference.getSnaks().equals( new wb.datamodel.SnakList( snakList ) ),
				'Snaks were set correctly'
			);
		} );
	} );

	QUnit.test( 'getHash', function( assert ) {
		var hash = 'hash12390213',
			reference = new wb.datamodel.Reference( [], hash );

		assert.equal(
			reference.getHash(),
			hash,
			'Reference\'s hash from constructor returned in getHash()'
		);

		reference.setSnaks( snakLists[0] );
		assert.equal(
			reference.getHash(),
			hash,
			'Reference\'s hash does not change when snak list changes'
		);

		assert.equal(
			( new wb.datamodel.Reference( [] ) ).getHash(),
			null,
			'Reference without initial hash will return null in getHash()'
		);
	} );

	QUnit.test( 'toJSON()', function( assert ) {
		var reference = new wb.datamodel.Reference(
			new wb.datamodel.SnakList(
				[
					new wb.datamodel.PropertyValueSnak( 'P42', new dv.StringValue( 'string' ) ),
					new wb.datamodel.PropertySomeValueSnak( 'P9001' )
				]
			),
			'hash12390213'
		);

		assert.ok(
			reference.equals( wb.datamodel.Reference.newFromJSON( reference.toJSON() ) ),
			'Exported reference to JSON.'
		);
	} );

	QUnit.test( 'equals()', function( assert ) {
		var references = [
			new wb.datamodel.Reference(),
			new wb.datamodel.Reference(
				new wb.datamodel.SnakList(
					[
						new wb.datamodel.PropertyValueSnak( 'P42', new dv.StringValue( 'string' ) ),
						new wb.datamodel.PropertySomeValueSnak( 'P9001' )
					]
				),
				'hash12390213'
			),
			new wb.datamodel.Reference(
				new wb.datamodel.SnakList(
					[
						new wb.datamodel.PropertyValueSnak( 'P345', new dv.StringValue( 'string' ) ),
						new wb.datamodel.PropertySomeValueSnak( 'P9001' )
					]
				)
			)
		];

		// Compare references:
		$.each( references, function( i, reference ) {
			var clonedReference = wb.datamodel.Reference.newFromJSON( reference.toJSON() );

			// Check if "cloned" reference is equal:
			assert.ok(
				reference.equals( clonedReference ),
				'Verified reference "' + i + '" on equality.'
			);

			// Compare to all other references:
			$.each( references, function( j, otherReference ) {
				if ( j !== i ) {
					assert.ok(
						!reference.equals( otherReference ),
						'Reference "' + i + '" is not equal to reference "'+ j + '".'
					);
				}
			} );

		} );

	} );

}( wikibase, dataValues, jQuery, QUnit ) );
