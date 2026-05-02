/**
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 */

( function( QUnit ) {
	'use strict';

	var Reference = require( '../src/Reference.js' ),
		SnakList = require( '../src/SnakList.js' ),
		PropertyNoValueSnak = require( '../src/PropertyNoValueSnak.js' ),
		PropertySomeValueSnak = require( '../src/PropertySomeValueSnak.js' );

	QUnit.module( 'Reference' );

	QUnit.test( 'constructor, getSnaks()', function( assert ) {
		assert.expect( 7 );
		var snakLists = [
			new SnakList( [] ),
			new SnakList( [new PropertyNoValueSnak( 'P1' )] ),
			new SnakList( [
				new PropertyNoValueSnak( 'P1' ),
				new PropertySomeValueSnak( 'P2' )
			] )
		];

		for( var i = 0; i < snakLists.length; i++ ) {
			var reference = new Reference( snakLists[i] );

			assert.ok(
				reference instanceof Reference,
				'Test set #' + i + ': Instantiated Reference object.'
			);

			assert.ok(
				reference.getSnaks().equals( snakLists[i] ),
				'Test set #' + i + ': Retrieved Snaks passed to the constructor.'
			);
		}

		assert.throws(
			function() {
				return new Reference( [new PropertyNoValueSnak( 'P1' )] );
			},
			'Throwing an error when trying to instantiate a Reference with a plain array of Snak '
			+ 'objects.'
		);
	} );

	QUnit.test( 'getHash()', function( assert ) {
		assert.expect( 2 );
		var hash = 'hash12390213';

		assert.equal(
			( new Reference( null, hash ) ).getHash(),
			hash,
			'Reference\'s hash from constructor returned in getHash()'
		);

		assert.equal(
			( new Reference() ).getHash(),
			null,
			'Reference without initial hash will return null in getHash()'
		);
	} );

	QUnit.test( 'equals()', function( assert ) {
		assert.expect( 9 );
		var references = [
			new Reference(),
			new Reference(
				new SnakList( [new PropertyNoValueSnak( 'P1' )] ),
				'hash12390213'
			),
			new Reference(
				new SnakList(
					[
						new PropertyNoValueSnak( 'P1' ),
						new PropertySomeValueSnak( 'P2' )
					]
				)
			)
		];

		// Compare references:
		for( var i = 0; i < references.length; i++ ) {
			var clonedReference = new Reference(
				references[i].getSnaks(),
				references[i].getHash()
			);

			// Check if "cloned" reference is equal:
			assert.ok(
				references[i].equals( clonedReference ),
				'Verified reference "' + i + '" on equality.'
			);

			// Compare to all other references:
			for( var j = 0; j < references.length; j++ ) {
				if ( j !== i ) {
					assert.ok(
						!references[i].equals( references[j] ),
						'Reference "' + i + '" is not equal to reference "'+ j + '".'
					);
				}
			}

		}

	} );

}( QUnit ) );
