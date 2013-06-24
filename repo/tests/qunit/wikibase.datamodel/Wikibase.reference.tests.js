/**
 * QUnit tests for wikibase.Reference
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.3
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, dv, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.Reference', QUnit.newWbEnvironment() );

	var snakLists = [
		new wb.SnakList( [
			new wb.PropertyNoValueSnak( 9001 ),
			new wb.PropertySomeValueSnak( 42 ),
			new wb.PropertyValueSnak( 23, new dv.StringValue( '~=[,,_,,]:3' ) )
		] ),
		new wb.SnakList( [] ),
		new wb.SnakList( [ new wb.PropertyNoValueSnak( 9001 ) ] )
	];

	QUnit.test( 'constructor', function( assert ) {
		$.each( snakLists, function( i, snakList ) {
			var reference = new wb.Reference( snakList );

			assert.ok(
				reference.getSnaks().equals( snakList ),
				'Snaks were set correctly'
			);
		} );
	} );

	QUnit.test( 'setSnaks and getSnaks', function( assert ) {
		$.each( snakLists, function( i, snakList ) {
			var reference = new wb.Reference( [] );

			reference.setSnaks( snakList );

			assert.ok(
				reference.getSnaks().equals( new wb.SnakList( snakList ) ),
				'Snaks were set correctly'
			);
		} );
	} );

	QUnit.test( 'getHash', function( assert ) {
		var hash = 'hash12390213',
			reference = new wb.Reference( [], hash );

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
			( new wb.Reference( [] ) ).getHash(),
			null,
			'Reference without initial hash will return null in getHash()'
		);
	} );

	QUnit.test( 'toJSON()', function( assert ) {
		var reference = new wb.Reference(
			new wb.SnakList(
				[
					new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ),
					new wb.PropertySomeValueSnak( 9001 )
				]
			),
			'hash12390213'
		);

		assert.ok(
			reference.equals( wb.Reference.newFromJSON( reference.toJSON() ) ),
			'Exported reference to JSON.'
		);
	} );

	QUnit.test( 'equals()', function( assert ) {
		var references = [
			new wb.Reference(),
			new wb.Reference(
				new wb.SnakList(
					[
						new wb.PropertyValueSnak( 42, new dv.StringValue( 'string' ) ),
						new wb.PropertySomeValueSnak( 9001 )
					]
				),
				'hash12390213'
			),
			new wb.Reference(
				new wb.SnakList(
					[
						new wb.PropertyValueSnak( 345, new dv.StringValue( 'string' ) ),
						new wb.PropertySomeValueSnak( 9001 )
					]
				)
			)
		];

		// Compare references:
		$.each( references, function( i, reference ) {
			var clonedReference = wb.Reference.newFromJSON( reference.toJSON() );

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
