/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, dv, $, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.Reference' );

	QUnit.test( 'constructor, getSnaks()', function( assert ) {
		var snakLists = [
			new wb.datamodel.SnakList( [] ),
			new wb.datamodel.SnakList( [new wb.datamodel.PropertyNoValueSnak( 'P1' )] ),
			new wb.datamodel.SnakList( [
				new wb.datamodel.PropertyNoValueSnak( 'P1' ),
				new wb.datamodel.PropertySomeValueSnak( 'P2' )
			] )
		];

		$.each( snakLists, function( i, snakList ) {
			var reference = new wb.datamodel.Reference( snakList );

			assert.ok(
				reference instanceof wb.datamodel.Reference,
				'Instantiated Reference object.'
			);

			assert.ok(
				reference.getSnaks().equals( new wb.datamodel.SnakList( snakList ) ),
				'Retrieved Snaks passed to the constructor.'
			);
		} );
	} );

	QUnit.test( 'getHash()', function( assert ) {
		var hash = 'hash12390213';

		assert.equal(
			( new wb.datamodel.Reference( [], hash ) ).getHash(),
			hash,
			'Reference\'s hash from constructor returned in getHash()'
		);

		assert.equal(
			( new wb.datamodel.Reference( [] ) ).getHash(),
			null,
			'Reference without initial hash will return null in getHash()'
		);
	} );

	QUnit.test( 'equals()', function( assert ) {
		var references = [
			new wb.datamodel.Reference(),
			new wb.datamodel.Reference(
				new wb.datamodel.SnakList( [new wb.datamodel.PropertyNoValueSnak( 'P1' )] ),
				'hash12390213'
			),
			new wb.datamodel.Reference(
				new wb.datamodel.SnakList(
					[
						new wb.datamodel.PropertyNoValueSnak( 'P1' ),
						new wb.datamodel.PropertySomeValueSnak( 'P2' )
					]
				)
			)
		];

		// Compare references:
		$.each( references, function( i, reference ) {
			var clonedReference = new wb.datamodel.Reference(
				reference.getSnaks(),
				reference.getHash()
			);

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
