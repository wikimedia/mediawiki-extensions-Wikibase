/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, wb, QUnit ) {
	'use strict';

/**
 * @param {Object} [options]
 * @return {jQuery}
 */
function createSitelinkgrouplistview( options ) {
	options = $.extend( {
		siteLinksChanger: 'siteLinksChanger',
		entityStore: 'i am an entity store'
	}, options );

	return $( '<div/>' )
		.addClass( 'test_sitelinkgrouplistview')
		.appendTo( $( 'body' ) )
		.sitelinkgrouplistview( options );
}

QUnit.module( 'jquery.wikibase.sitelinkgrouplistview', QUnit.newWbEnvironment( {
	config: {
		'wbSiteDetails': {
			aawiki: {
				apiUrl: 'http://aa.wikipedia.org/w/api.php',
				name: 'Qafár af',
				pageUrl: 'http://aa.wikipedia.org/wiki/$1',
				shortName: 'Qafár af',
				languageCode: 'aa',
				id: 'aawiki',
				group: 'group1'
			},
			enwiki: {
				apiUrl: 'http://en.wikipedia.org/w/api.php',
				name: 'English Wikipedia',
				pageUrl: 'http://en.wikipedia.org/wiki/$1',
				shortName: 'English',
				languageCode: 'en',
				id: 'enwiki',
				group: 'group1'
			},
			dewiki: {
				apiUrl: 'http://de.wikipedia.org/w/api.php',
				name: 'Deutsche Wikipedia',
				pageUrl: 'http://de.wikipedia.org/wiki/$1',
				shortName: 'Deutsch',
				languageCode: 'de',
				id: 'dewiki',
				group: 'group2'
			}
		}
	},
	teardown: function() {
		$( '.test_sitelinkgrouplistview' ).each( function() {
			var $sitelinkgrouplistview = $( this ),
				sitelinkgrouplistview = $sitelinkgrouplistview.data( 'sitelinkgrouplistview' );

			if( sitelinkgrouplistview ) {
				sitelinkgrouplistview.destroy();
			}

			$sitelinkgrouplistview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	var value = [
		{
			group: 'group1',
			siteLinks: [new wb.datamodel.SiteLink( 'enwiki', 'page1' )]
		}, {
			group: 'group2',
			siteLinks: [
				new wb.datamodel.SiteLink( 'dewiki', 'page1' ),
				new wb.datamodel.SiteLink( 'enwiki', 'page2' )
			]
		}
	];

	var $sitelinkgrouplistview = createSitelinkgrouplistview( {
			value: value
		} ),
		sitelinkgrouplistview = $sitelinkgrouplistview.data( 'sitelinkgrouplistview' );

	assert.ok(
		sitelinkgrouplistview !== undefined,
		'Created widget.'
	);

	sitelinkgrouplistview.destroy();

	assert.ok(
		$sitelinkgrouplistview.data( 'sitelinkview' ) === undefined,
		'Destroyed widget.'
	);

	assert.throws( function() {
			$sitelinkgrouplistview = createSitelinkgrouplistview();
		},
		'Widget does not accept an empty value.'
	);
} );

}( jQuery, wikibase, QUnit ) );
