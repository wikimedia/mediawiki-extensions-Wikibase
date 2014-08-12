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
function createSitegroupview( options ) {
	options = $.extend( {
		entityId: 'i am an entity id',
		api: 'i am an api',
		entityStore: new wb.store.EntityStore( null )
	}, options );

	return $( '<div/>' )
		.addClass( 'test_sitelinkgroupview')
		.appendTo( $( 'body' ) )
		.sitelinkgroupview( options );
}

QUnit.module( 'jquery.wikibase.sitelinkgroupview', QUnit.newWbEnvironment( {
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
		$( '.test_sitelinkgroupview' ).each( function() {
			var $sitelinkgroupview = $( this ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' );

			if( sitelinkgroupview ) {
				sitelinkgroupview.destroy();
			}

			$sitelinkgroupview.remove();
		} );
	}
} ) );

QUnit.test( 'Create and destroy', function( assert ) {
	var siteLink = new wikibase.datamodel.SiteLink( 'enwiki', 'Main Page' ),
		$sitelinkgroupview = createSitegroupview( {
			value: { group: 'group1', siteLinks: [siteLink] }
		} ),
		sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' );

	assert.ok(
		sitelinkgroupview !== undefined,
		'Created widget'
	);

	sitelinkgroupview.destroy();

	assert.ok(
		$sitelinkgroupview.data( 'sitelinkview' ) === undefined,
		'Destroyed widget.'
	);

	assert.throws( function() {
			$sitelinkgroupview = createSitegroupview();
		},
		'Widget does not accept an empty value.'
	);
} );

}( jQuery, wikibase, QUnit ) );
