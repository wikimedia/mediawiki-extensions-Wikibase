/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit ) {
	'use strict';

var Item = require( '../src/Item.js' ),
	SiteLinkSet = require( '../src/SiteLinkSet.js' ),
	SiteLink = require( '../src/SiteLink.js' ),
	Statement = require( '../src/Statement.js' ),
	StatementGroup = require( '../src/StatementGroup.js' ),
	StatementGroupSet = require( '../src/StatementGroupSet.js' ),
	StatementList = require( '../src/StatementList.js' ),
	Claim = require( '../src/Claim.js' ),
	PropertyNoValueSnak = require( '../src/PropertyNoValueSnak.js' ),
	Fingerprint = require( '../src/Fingerprint.js' ),
	Term = require( '../src/Term.js' ),
	MultiTerm = require( '../src/MultiTerm.js' ),
	TermMap = require( '../src/TermMap.js' ),
	MultiTermMap = require( '../src/MultiTermMap.js' );

QUnit.module( 'Item' );

var testSets = [
	[
		'Q1',
		new Fingerprint(
			new TermMap(),
			new TermMap(),
			new MultiTermMap()
		),
		new StatementGroupSet(),
		new SiteLinkSet()
	], [
		'Q2',
		new Fingerprint(
			new TermMap( { de: new Term( 'de', 'de-label' ) } ),
			new TermMap( { en: new Term( 'de', 'de-description' ) } ),
			new MultiTermMap( {
				de: new MultiTerm( 'de', ['de-alias'] )
			} )
		),
		new StatementGroupSet( [
			new StatementGroup( 'P1',
				new StatementList( [
					new Statement(
						new Claim( new PropertyNoValueSnak( 'P1' ) )
					)
				] )
			)
		] ),
		new SiteLinkSet( [
			new SiteLink( 'de', 'de-page' )
		] )
	]
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.length; i++ ) {
		var item = new Item(
			testSets[i][0], testSets[i][1], testSets[i][2], testSets[i][3]
		);
		assert.ok(
			item instanceof Item,
			'Instantiated Item object.'
		);
	}
} );

QUnit.test( 'isEmpty()', function( assert ) {
	assert.expect( 4 );
	assert.ok(
		( new Item(
			'Q1',
			new Fingerprint(
				new TermMap(),
				new TermMap(),
				new MultiTermMap()
			),
			new StatementGroupSet(),
			new SiteLinkSet()
		) ).isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	assert.ok(
		!( new Item(
			'Q1',
			new Fingerprint(
				new TermMap( { de: new Term( 'de', 'de-term' ) } ),
				new TermMap(),
				new MultiTermMap()
			),
			new StatementGroupSet(),
			new SiteLinkSet()
		) ).isEmpty(),
		'Returning FALSE when Fingerprint is not empty.'
	);

	assert.ok(
		!( new Item(
			'Q1',
			new Fingerprint(
				new TermMap(),
				new TermMap(),
				new MultiTermMap()
			),
			new StatementGroupSet(),
			new SiteLinkSet( [new SiteLink( 'de', 'de-page' )] )
		) ).isEmpty(),
		'Returning FALSE when SiteLinkSet is not empty.'
	);

	assert.ok(
		!( new Item(
			'Q1',
			new Fingerprint(
				new TermMap(),
				new TermMap(),
				new MultiTermMap()
			),
			new StatementGroupSet( [
				new StatementGroup( 'P1',
					new StatementList( [new Statement(
						new Claim( new PropertyNoValueSnak( 'P1' ) )
					)] )
				)
			] ),
			new SiteLinkSet()
		) ).isEmpty(),
		'Returning FALSE when StatementGroupSet is not empty.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	assert.expect( 4 );
	for( var i = 0; i < testSets.length; i++ ) {
		var item1 = new Item(
			testSets[i][0], testSets[i][1], testSets[i][2], testSets[i][3]
		);

		for( var j = 0; j < testSets.length; j++ ) {
			var item2 = new Item(
				testSets[j][0], testSets[j][1], testSets[j][2], testSets[j][3]
			);

			if( i === j ) {
				assert.ok(
					item1.equals( item2 ),
					'Test set #' + i + ' equals test set #' + j + '.'
				);
				continue;
			}

			assert.ok(
				!item1.equals( item2 ),
				'Test set #' + i + ' does not equal test set #' + j + '.'
			);
		}
	}
} );

}( QUnit ) );
