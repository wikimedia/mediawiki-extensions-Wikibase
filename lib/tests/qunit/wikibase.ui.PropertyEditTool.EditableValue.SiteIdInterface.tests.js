/**
 * QUnit tests for EditableValue.SiteIdInterface
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	/**
	 * Factory for creating a new SiteIdInteface object suited for testing.
	 *
	 * @param {jQuery} [$node]
	 * @return  {wb.ui.PropertyEditTool.EditableValue.SiteIdInterface}
	 */
	var newTestSiteIdInterface = function( $node, options ) {
		if ( options === undefined ) {
			options = {};
		}
		return new wb.ui.PropertyEditTool.EditableValue.SiteIdInterface( $node, options );
	};

	var siteIds = ['en', 'de'];

	QUnit.module( 'wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface', QUnit.newWbEnvironment( {
		config: {
			'wbSiteDetails': {
				en: {
					apiUrl: 'http://en.wikipedia.org/w/api.php',
					id: 'en',
					name: 'English Wikipedia',
					pageUrl: 'http://en.wikipedia.org/wiki/$1',
					shortName: 'English'
				},
				de: {
					apiUrl: 'http://de.wikipedia.org/w/api.php',
					id: 'de',
					name: 'Deutsche Wikipedia',
					pageUrl: 'http://de.wikipedia.org/wiki/$1',
					shortName: 'Deutsch'
				}
			}
		}
	} ) );

	QUnit.test( 'init input', function( assert ) {
		var $node = $( '<div/>', { id: 'subject' } );
		var siteIdInterface = newTestSiteIdInterface( $node );

		assert.ok(
			siteIdInterface._subject[0] === $node[0],
			'validated subject'
		);

		siteIdInterface.startEditing();

		assert.equal(
			siteIdInterface._currentResults.length,
			2,
			'filled result set'
		);

		assert.equal(
			siteIdInterface.getSelectedSiteId(),
			null,
			'no site id selected'
		);

		assert.equal(
			siteIdInterface.setValue( wb.getSite( 'en' ).getId() ),
			wb.getSite( siteIds[0] ).getId(),
			'set value to site id'
		);

		assert.equal(
			siteIdInterface.getSelectedSite(),
			wb.getSite( siteIds[0] ),
			'verified selected site'
		);

		assert.equal(
			siteIdInterface.setValue( siteIdInterface._currentResults[1].label ),
			wb.getSite( siteIds[1] ).getId(),
			'set value to input option label'
		);

		assert.equal(
			siteIdInterface.getSelectedSite(),
			wb.getSite( siteIds[1] ),
			'verified selected site'
		);

		assert.equal(
			siteIdInterface.setValue( siteIdInterface._currentResults[0].value ),
			wb.getSite( siteIds[0] ).getId(),
			'set value to input option value'
		);

		assert.equal(
			siteIdInterface.getSelectedSite(),
			wb.getSite( siteIds[0] ),
			'verified selected site'
		);

		assert.equal(
			siteIdInterface.setValue( '' ),
			null,
			'Set value to an empty string.'
		);

		assert.equal(
			siteIdInterface._inputElem.data( 'siteselector' ).getSelectedSiteId(),
			null,
			'No site id selected.'
		);

		var testSite = wb.getSite( siteIds[0] );
		var testStrings = [
			testSite.getId(),
			testSite.getShortName(),
			testSite.getName() + ' (' + testSite.getId() + ')'
		];

		$.each( testStrings, function( index, val ) {
			assert.equal(
				siteIdInterface.setValue( val ),
				testSite.getId(),
				'Set value to "' + val + '"'
			);

			assert.equal(
				siteIdInterface._inputElem.data( 'siteselector' ).getSelectedSiteId(),
				testSite.getId(),
				'get result-set match from string "' + val + '"'
			);
		} );

		siteIdInterface.destroy();

		assert.equal(
			$( siteIdInterface._getValueContainer()[0] ).children().length,
			0,
			'no input element'
		);

	} );

	QUnit.test( 'init input with blacklist', function( assert ) {
		var $node = $( '<div/>', { id: 'subject' } );
		var siteIdInterface = newTestSiteIdInterface( $node, {
			ignoredSiteLinks: [ siteIds[1] ]
		} );

		siteIdInterface.startEditing();

		assert.equal(
			siteIdInterface._currentResults.length,
			1,
			'filled result set'
		);

		assert.equal(
			siteIdInterface.getSelectedSiteId(),
			null,
			'no site id slected'
		);

		// set this to valid value
		siteIdInterface.setValue( wb.getSite( siteIds[0] ).getId() );

		assert.equal(
			siteIdInterface.isValid(),
			true,
			'current value is valid'
		);

		// for next test, do this the hard way since setValue() would reject invalid value (value in blacklist)
		siteIdInterface._inputElem.val( wb.getSite( siteIds[1] ).getId() );

		assert.equal(
			siteIdInterface.isValid(),
			false,
			'value set to blacklisted site id should be invalid'
		);

		assert.equal(
			siteIdInterface.getSelectedSite(),
			null,
			'no site id selected'
		);

		assert.equal(
			siteIdInterface.setValue( wb.getSite( siteIds[0] ).getId() ),
			wikibase.getSite( siteIds[0] ).getId(),
			'set value to valid site id'
		);

		assert.equal(
			siteIdInterface.getSelectedSite(),
			wb.getSite( siteIds[0] ),
			'verified selected site'
		);

	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
