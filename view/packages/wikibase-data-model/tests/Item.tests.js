/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
	'use strict';

QUnit.module( 'wikibase.datamodel.Item' );

var testSets = [
	[
		'Q1',
		new wb.datamodel.Fingerprint(
			new wb.datamodel.TermMap(),
			new wb.datamodel.TermMap(),
			new wb.datamodel.MultiTermMap()
		),
		new wb.datamodel.StatementGroupSet(),
		new wb.datamodel.SiteLinkSet()
	], [
		'Q2',
		new wb.datamodel.Fingerprint(
			new wb.datamodel.TermMap( { de: new wb.datamodel.Term( 'de', 'de-label' ) } ),
			new wb.datamodel.TermMap( { en: new wb.datamodel.Term( 'de', 'de-description' ) } ),
			new wb.datamodel.MultiTermMap( {
				de: new wb.datamodel.MultiTerm( 'de', ['de-alias'] )
			} )
		),
		new wb.datamodel.StatementGroupSet( [
			new wb.datamodel.StatementGroup( 'P1',
				new wb.datamodel.StatementList( [
					new wb.datamodel.Statement(
						new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
					)
				] )
			)
		] ),
		new wb.datamodel.SiteLinkSet( [
			new wb.datamodel.SiteLink( 'de', 'de-page' )
		] )
	]
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.length; i++ ) {
		var item = new wb.datamodel.Item(
			testSets[i][0], testSets[i][1], testSets[i][2], testSets[i][3]
		);
		assert.ok(
			item instanceof wb.datamodel.Item,
			'Instantiated Item object.'
		);
	}
} );

QUnit.test( 'isEmpty()', function( assert ) {
	assert.expect( 4 );
	assert.ok(
		( new wb.datamodel.Item(
			'Q1',
			new wb.datamodel.Fingerprint(
				new wb.datamodel.TermMap(),
				new wb.datamodel.TermMap(),
				new wb.datamodel.MultiTermMap()
			),
			new wb.datamodel.StatementGroupSet(),
			new wb.datamodel.SiteLinkSet()
		) ).isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	assert.ok(
		!( new wb.datamodel.Item(
			'Q1',
			new wb.datamodel.Fingerprint(
				new wb.datamodel.TermMap( { de: new wb.datamodel.Term( 'de', 'de-term' ) } ),
				new wb.datamodel.TermMap(),
				new wb.datamodel.MultiTermMap()
			),
			new wb.datamodel.StatementGroupSet(),
			new wb.datamodel.SiteLinkSet()
		) ).isEmpty(),
		'Returning FALSE when Fingerprint is not empty.'
	);

	assert.ok(
		!( new wb.datamodel.Item(
			'Q1',
			new wb.datamodel.Fingerprint(
				new wb.datamodel.TermMap(),
				new wb.datamodel.TermMap(),
				new wb.datamodel.MultiTermMap()
			),
			new wb.datamodel.StatementGroupSet(),
			new wb.datamodel.SiteLinkSet( [new wb.datamodel.SiteLink( 'de', 'de-page' )] )
		) ).isEmpty(),
		'Returning FALSE when SiteLinkSet is not empty.'
	);

	assert.ok(
		!( new wb.datamodel.Item(
			'Q1',
			new wb.datamodel.Fingerprint(
				new wb.datamodel.TermMap(),
				new wb.datamodel.TermMap(),
				new wb.datamodel.MultiTermMap()
			),
			new wb.datamodel.StatementGroupSet( [
				new wb.datamodel.StatementGroup( 'P1',
					new wb.datamodel.StatementList( [new wb.datamodel.Statement(
						new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
					)] )
				)
			] ),
			new wb.datamodel.SiteLinkSet()
		) ).isEmpty(),
		'Returning FALSE when StatementGroupSet is not empty.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	assert.expect( 4 );
	for( var i = 0; i < testSets.length; i++ ) {
		var item1 = new wb.datamodel.Item(
			testSets[i][0], testSets[i][1], testSets[i][2], testSets[i][3]
		);

		for( var j = 0; j < testSets.length; j++ ) {
			var item2 = new wb.datamodel.Item(
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

}( wikibase, QUnit ) );
