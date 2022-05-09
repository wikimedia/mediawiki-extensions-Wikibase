/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	/**
	 * Site details as required by the wikibase.Site constructor.
	 *
	 * @type {Object[]}
	 */
	var siteDetails = [
		{
			apiUrl: 'http://en.wikipedia.org/w/api.php',
			name: 'English',
			pageUrl: 'http://en.wikipedia.org/wiki/$1',
			shortName: 'English',
			languageCode: 'en',
			id: 'enwiki',
			group: 'whatever'
		},
		{
			apiUrl: 'http://de.wikipedia.org/w/api.php',
			name: 'Deutsch',
			pageUrl: 'http://de.wikipedia.org/wiki/$1',
			shortName: 'Deutsch',
			languageCode: 'de',
			id: 'dewiki',
			group: 'another'
		},
		{
			apiUrl: 'http://no.wikipedia.org/w/api.php',
			name: 'norsk bokmål',
			pageUrl: 'http://no.wikipedia.org/wiki/$1',
			shortName: 'norsk bokmål',
			languageCode: 'no',
			id: 'nowiki',
			group: 'foo'
		},
		{
			apiUrl: 'http://frrwiki.wikipedia.org/w/api.php',
			name: 'Nordfriisk',
			pageUrl: 'http://frrwiki.wikipedia.org/wiki/$1',
			shortName: 'Nordfriisk',
			languageCode: 'frr',
			id: 'frrwiki',
			group: 'foo'
		},
		{
			apiUrl: 'http://zh-min-nan.wikipedia.org/w/api.php',
			name: 'Chinese',
			pageUrl: 'http://zh-min-nan.wikipedia.org/wiki/$1',
			shortName: 'Chinese',
			languageCode: 'zh-min-nan',
			id: 'zh_min_nan',
			group: 'dummy'
		}
	];

	/**
	 * @type {wikibase.Site[]}
	 */
	var sites = [];

	for ( var i = 0; i < siteDetails.length; i++ ) {
		sites.push( new wb.Site( siteDetails[ i ] ) );
	}

	/**
	 * Returns the predefined site featuring a specific site id.
	 *
	 * @param {string} siteId
	 * @return {wikibase.Site|null}
	 */
	function getSite( siteId ) {
		for ( var j = 0; j < sites.length; j++ ) {
			if ( sites[ j ].getId() === siteId ) {
				return sites[ j ];
			}
		}
		return null;
	}

	/**
	 * Factory creating a new siteselector enhanced input element.
	 *
	 * @param {Object} [options]
	 * @return  {jQuery} input element
	 */
	var newTestSiteSelector = function ( options ) {
		options = $.extend( { source: sites }, options || {} );

		return $( '<input>' )
			.addClass( 'test-siteselector' )
			.appendTo( 'body' )
			.trigger( 'focus' )
			.siteselector( options );
	};

	QUnit.module( 'jquery.wikibase.siteselector', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test-siteselector' ).each( function ( j, node ) {
				var $node = $( node );
				if ( $node.data( 'siteselector' ) ) {
					$node.data( 'siteselector' ).destroy();
				}
				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'getSelectedSite()', function ( assert ) {
		var $siteSelector = newTestSiteSelector(),
			siteSelector = $siteSelector.data( 'siteselector' );

		/**
		 * @type {Array[]}
		 */
		var testStrings = [
			[ 'en', 'enwiki' ],
			[ 'd', 'dewiki' ],
			[ 'English (enwiki)', 'enwiki' ],
			[ 'deutsch', 'dewiki' ],
			[ 'no', 'nowiki' ], // Prefer language code.
			[ 'enwiki', 'enwiki' ],
			[ 'nord', 'frrwiki' ],
			[ 'https://zh-min-nan.wikipedia.org/wiki/Dummy', 'zh_min_nan' ],
			[ 'https://de.wikipedia.org/wiki/', 'dewiki' ],
			[ 'https://zh-min-nan.wikipedia.org', 'zh_min_nan' ],
			[ '//de.wikipedia.org/wiki/Dummy', 'dewiki' ],
			[ '//zh-min-nan.wikipedia.org', 'zh_min_nan' ],
			[ '(de)', 'dewiki' ],
			[ 'zh-min-nan.wikipedia.org/wiki/Dummy', 'zh_min_nan' ],
			[ 'de.wikipedia.org', 'dewiki' ],
			[ 'zh-min-nan/de', 'zh_min_nan' ]
		];

		/**
		 * @param {string} string
		 * @param {string} expectedSiteId
		 * @param {Function} next
		 */
		var testString = function ( string, expectedSiteId, next ) {
			$siteSelector.val( string );

			var done = assert.async();

			$siteSelector.one( 'siteselectorselected', function ( event, siteId ) {
				assert.strictEqual(
					siteId,
					expectedSiteId,
					'Triggered "selected" event returning site id: "' + siteId + '".'
				);
			} );

			$siteSelector.one( 'siteselectoropen', function () {
				// siteselector sets the selected site on the "siteselector" open. So, defer
				// checking selected site:
				setTimeout( function () {
					assert.strictEqual(
						siteSelector.getSelectedSite(),
						expectedSiteId ? getSite( expectedSiteId ) : null,
						'Implicitly selected expected site "' + ( expectedSiteId || 'NULL' )
							+ '" using input "' + string + '".'
					);
					siteSelector._close();
					done();
					next();
				}, 0 );

			} );

			siteSelector.search()
			.done( function ( suggestions ) {
				assert.strictEqual(
					suggestions.length > 0 ? suggestions[ 0 ] : null,
					expectedSiteId ? getSite( expectedSiteId ) : null,
					'Returned expected first suggestion "' + ( expectedSiteId || 'NULL' )
						+ '" using input "' + string + '".'
				);

				if ( !suggestions.length ) {
					done();
					next();
				}
			} )
			.fail( function () {
				QUnit.ok(
					false,
					'Search failed.'
				);
				done();
				next();
			} );
		};

		var $queue = $( {} );

		/**
		 * @param {Array} testSet
		 */
		function addToQueue( testSet ) {
			$queue.queue( 'tests', function ( next ) {
				testString( testSet[ 0 ], testSet[ 1 ], next );
			} );
		}

		for ( var j = 0; j < testStrings.length; j++ ) {
			addToQueue( testStrings[ j ] );
		}

		// Reset selected site by clearing input:
		$queue.queue( 'tests', function ( next ) {
			testString( '', null, next );
		} );

		$queue.queue( 'tests', function ( next ) {
			testString( 'doesnotexist', null, next );
		} );

		$queue.dequeue( 'tests' );
	} );

	QUnit.test( 'Create passing a source function', function ( assert ) {
		var $siteSelector = newTestSiteSelector( {
				source: function () {
					return sites.slice( 0, 2 );
				}
			} ),
			siteSelector = $siteSelector.data( 'siteselector' );

		var done = assert.async();

		$siteSelector.val( 'en' );

		siteSelector.search()
		.done( function ( suggestions ) {
			assert.strictEqual(
				suggestions[ 0 ].getId(),
				'enwiki',
				'Returned expected first suggestion "enwiki".'
			);
		} )
		.fail( function () {
			QUnit.ok(
				false,
				'Search failed.'
			);
		} )
		.always( function () {
			$siteSelector.val( 'frr' );

			siteSelector.search()
			.done( function ( suggestions ) {
				assert.strictEqual(
					suggestions.length,
					0,
					'Did not return unexpected suggestions.'
				);
			} )
			.fail( function () {
				QUnit.ok(
					false,
					'Search failed.'
				);
			} )
			.always( done );
		} );
	} );

	QUnit.test( 'Item constructor', function ( assert ) {
		var item = new $.wikibase.siteselector.Item( 'label', 'value', sites[ 0 ] );

		assert.true(
			item instanceof $.wikibase.siteselector.Item,
			'Instantiated default siteselector item.'
		);

		assert.throws(
			function () {
				item = new $.wikibase.siteselector.Item( 'label', 'value' );
			},
			'Throwing error when omitting site on instantiation.'
		);
	} );

}( wikibase ) );
