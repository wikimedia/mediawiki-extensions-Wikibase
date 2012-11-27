/**
 * QUnit tests jquery.wikibase.siteselector widget
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki at snater.com >
 */

( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	/**
	 * Factory for creating a new sitesuggester enhanced input element.
	 *
	 * @param {Object} [options]
	 * @return  {jQuery} input element
	 */
	var newTestSiteSelector = function( options ) {
		options = options || {};

		var siteList = [];
		for ( var key in wb.getSites() ) {
			siteList.push( wb.getSites()[key] );
		}

		options = $.merge( { resultSet: siteList }, options );
		var input = $( '<input/>' ).siteselector( options );

		input.data( 'siteselector' ).test_search = function( string ) {
			this.element.val( string );

			// trigger opening menu without setTimeout delay invoked in jquery.ui.autocomplete
			this.search( string );

			return this.getSelectedSiteId();
		};

		return input;
	};

	QUnit.module( 'jquery.wikibase.siteselector', QUnit.newWbEnvironment( {
		config: {
			'wbSiteDetails': {
				en: {
					id: 'en',
					name: 'English Wikipedia',
					shortName: 'English'
				},
				de: {
					id: 'de',
					name: 'Deutsche Wikipedia',
					shortName: 'Deutsch'
				}
			}
		},
		teardown: function() { $( '.wikibase-siteselector-list' ).remove(); }
	} ) );

	QUnit.test( 'Site detection', function( assert ) {
		var input = newTestSiteSelector(),
			siteselector = input.data( 'siteselector' ),
			testStrings = [
				{ en: 'en' },
				{ de: 'd' },
				{ en: 'English (en)'},
				{ de: 'deutsch' }
			];

		var testString = function( string, expectedSiteId ) {
			assert.equal(
				siteselector.test_search( string ),
				expectedSiteId,
				'Selected "' + expectedSiteId + '" by specifying "' + string + '".'
			);
		};

		for ( var i in testStrings ) {
			for ( var siteId in testStrings[i] ) {
				testString( testStrings[i][siteId], siteId );

				if ( i === 0 ) { // testing getSelectedSite() once is enough
					assert.equal(
						siteselector.getSelectedSite().getId(),
						siteId,
						'Retrieved correct wikibase Site object.'
					);
				}
			}
		}

		assert.equal(
			siteselector.test_search( 'en-doesnotexist' ),
			null,
			'No site selected after filling input box with a not existing value.'
		);

		assert.equal(
			siteselector.test_search( '' ),
			null,
			'No site selected after clearing input box.'
		);

	} );

	QUnit.test( 'Update result set', function( assert ) {
		var input = newTestSiteSelector(),
			siteselector = input.data( 'siteselector' ),
			siteList = [];

		for ( var key in wb.getSites() ) {
			siteList.push( wb.getSites()[key] );
		}

		siteselector.setResultSet( [] );

		assert.equal(
			siteselector.test_search( 'en' ),
			null,
			'No site found after having cleared the result set.'
		);

		siteselector.setResultSet( siteList );

		assert.equal(
			siteselector.test_search( 'en' ),
			'en',
			'Found site id after re-filling the result set.'
		);

	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
