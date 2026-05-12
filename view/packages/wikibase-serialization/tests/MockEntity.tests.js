/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'MockEntity' );

	var datamodel = require( 'wikibase.datamodel' ),
		MockEntity = require( './MockEntity.js' );

	var testSets = [
		[
			'i am an id',
			new datamodel.Fingerprint(
				new datamodel.TermMap(),
				new datamodel.TermMap(),
				new datamodel.MultiTermMap()
			)
		], [
			'i am an id',
			new datamodel.Fingerprint(
				new datamodel.TermMap( { de: new datamodel.Term( 'de', 'de-label' ) } ),
				new datamodel.TermMap( { de: new datamodel.Term( 'de', 'de-description' ) } ),
				new datamodel.MultiTermMap( {
					de: new datamodel.MultiTerm( 'de', [ 'de-alias' ] )
				} )
			)
		]
	];

	QUnit.test( 'Constructor', function( assert ) {
		assert.expect( 2 );
		for( var i = 0; i < testSets.length; i++ ) {
			var mockEntity = new MockEntity( testSets[i][0], testSets[i][1] );
			assert.ok(
				mockEntity instanceof MockEntity,
				'Test set #' + i + ': Instantiated MockEntity object.'
			);
		}
	} );

	QUnit.test( 'isEmpty()', function( assert ) {
		assert.expect( 2 );
		assert.strictEqual(
			( new MockEntity(
				'i am an id',
				new datamodel.Fingerprint(
					new datamodel.TermMap(),
					new datamodel.TermMap(),
					new datamodel.MultiTermMap()
				)
			) ).isEmpty(),
			true,
			'Verified isEmpty() returning TRUE.'
		);

		assert.strictEqual(
			( new MockEntity(
				'i am an id',
				new datamodel.Fingerprint(
					new datamodel.TermMap( { de: new datamodel.Term( 'de', 'de-term' ) } ),
					new datamodel.TermMap(),
					new datamodel.MultiTermMap()
				)
			) ).isEmpty(),
			false,
			'Returning FALSE when Fingerprint is not empty.'
		);
	} );

	QUnit.test( 'equals()', function( assert ) {
		assert.expect( 4 );
		for( var i = 0; i < testSets.length; i++ ) {
			var property1 = new MockEntity( testSets[i][0], testSets[i][1] );

			for( var j = 0; j < testSets.length; j++ ) {
				var property2 = new MockEntity( testSets[j][0], testSets[j][1] );

				if( i === j ) {
					assert.strictEqual(
						property1.equals( property2 ),
						true,
						'Test set #' + i + ' equals test set #' + j + '.'
					);
					continue;
				}

				assert.strictEqual(
					property1.equals( property2 ),
					false,
					'Test set #' + i + ' does not equal test set #' + j + '.'
				);
			}
		}
	} );

}() );
