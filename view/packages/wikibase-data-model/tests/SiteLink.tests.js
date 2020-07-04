/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit ) {
'use strict';

var SiteLink = require( '../src/SiteLink.js' );

QUnit.module( 'SiteLink' );

QUnit.test( 'Basic tests', function( assert ) {
	assert.expect( 2 );
	var siteLink = new SiteLink( 'test-id', 'test-name' );

	assert.equal(
		siteLink.getSiteId(),
		'test-id',
		'Verified site id.'
	);

	assert.equal(
		siteLink.getPageName(),
		'test-name',
		'Verified page name.'
	);
} );

QUnit.test( 'Badges', function( assert ) {
	assert.expect( 2 );
	var siteLink = new SiteLink( 'test-id', 'test-page' ),
		badges = ['Q123', 'Q456'];

	assert.equal(
		siteLink.getBadges().length,
		0,
		'Instantiated site link with no badges.'
	);

	siteLink = new SiteLink( 'test-id', 'test-page', badges );

	assert.equal(
		badges.join( ',' ),
		siteLink.getBadges().join( ',' ),
		'Instantiated site link with badges.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	assert.expect( 72 );
	var testSet = [
		['siteId', 'pageName', []],
		['anotherSiteId', 'pageName', []],
		['siteId', 'anotherPageName', []],
		['anotherSiteId', 'anotherPageName', []],
		['siteId', 'pageName', ['badgeId']],
		['siteId', 'pageName', ['badgeId', 'anotherBadgeId']]
	];

	var invalid = [
		'plain string',
		1,
		0,
		false,
		true,
		['siteId', 'pageName', []]
	];

	for( var i = 0; i < testSet.length; i++ ) {
		var siteLink1 = new SiteLink(
			testSet[i][0],
			testSet[i][1],
			testSet[i][2]
		);

		for( var j = 0; j < invalid.length; j++ ) {
			assert.ok(
				!siteLink1.equals( invalid[j] ),
				'Test set #' + i + ' is not equal to invalid set #' + j + '.'
			);
		}

		for( j = 0; j < testSet.length; j++ ) {
			var siteLink2 = new SiteLink(
				testSet[j][0],
				testSet[j][1],
				testSet[j][2]
			);

			if( i === j ) {
				assert.ok(
					siteLink1.equals( siteLink2 ),
					'Test set #' + i + ' equals.'
				);
			} else {
				assert.ok(
					!siteLink1.equals( siteLink2 ),
					'Test set #' + j + ' is not equal to test set #' + i + '.'
				);
			}
		}
	}

} );

}( QUnit ) );
