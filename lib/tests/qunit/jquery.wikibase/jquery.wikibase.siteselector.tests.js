/**
 * QUnit tests jquery.wikibase.siteselector widget
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
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
			var site = wb.getSites()[key];
			siteList.push( {
				'label': site.getName() + ' (' + site.getId() + ')',
				'value': site.getShortName() + ' (' + site.getId() + ')',
				'site': site // additional reference to site object for validation
			} );
		}

		options = $.merge( { resultSet: siteList }, options );
		return $( '<input/>' ).siteselector( options );
	};

	QUnit.module( 'wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface', QUnit.newWbEnvironment( {
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
		}
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
			input.val( string );

			// trigger opening menu without setTimeout delay invoked in jquery.ui.autocomplete
			siteselector.search( string );

			assert.equal(
				siteselector.getSelectedSiteId(),
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

		input.val( 'en-doesnotexist' ).trigger( 'keydown' );

		assert.equal(
			siteselector.getSelectedSiteId(),
			null,
			'No site selected after filling input box with a not existing value.'
		);

		input.val( '' ).trigger( 'keydown' );

		assert.equal(
			siteselector.getSelectedSiteId(),
			null,
			'No site selected after clearing input box.'
		);

	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
