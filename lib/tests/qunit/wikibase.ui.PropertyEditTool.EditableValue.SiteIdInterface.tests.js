/**
 * QUnit tests for EditableValue.SiteIdInterface
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
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

	var siteIds = ['enwiki', 'dewiki'];

	QUnit.module( 'wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface', QUnit.newWbEnvironment( {
		config: {
			'wbSiteDetails': {
				enwiki: {
					apiUrl: 'http://en.wikipedia.org/w/api.php',
					name: 'English Wikipedia',
					shortName: 'English',
					pageUrl: 'http://en.wikipedia.org/wiki/$1',
					languageCode: 'en',
					id: 'enwiki',
					group: 'whatever'
				},
				dewiki: {
					apiUrl: 'http://de.wikipedia.org/w/api.php',
					name: 'Deutsche Wikipedia',
					shortName: 'Deutsch',
					pageUrl: 'http://de.wikipedia.org/wiki/$1',
					languageCode: 'de',
					id: 'dewiki',
					group: 'another'
				}
			}
		},
		teardown: function() { $( '.wikibase-siteselector-list' ).remove(); }
	} ) );

	QUnit.test( 'initialization and destruction', function( assert ) {
		var $node = $( '<div/>', { id: 'subject' } ),
			siteIdInterface = newTestSiteIdInterface( $node );

		assert.strictEqual(
			siteIdInterface.getSubject()[0],
			$node[0],
			'verified subject'
		);

		siteIdInterface.startEditing();

		assert.equal(
			siteIdInterface._currentResults.length,
			2,
			'filled result set after startEditing()'
		);

		assert.equal(
			siteIdInterface.getSelectedSiteId(),
			null,
			'no site id selected initially'
		);

		siteIdInterface.destroy();

		assert.equal(
			$( siteIdInterface._getValueContainer()[0] ).children().length,
			0,
			'no input element'
		);
	} );

	QUnit.test( 'valid input resulting into some site being selected', function( assert ) {


		$.each( siteIds, function( i, siteId ) {
			var testSite = wb.getSite( siteId );
			var inputStrings = {
				'site\'s ID': testSite.getId()
				// TODO/FIXME: the following input strings have been used in the test before but
				//  the assertions only succeeded because the first defined string was a valid one
				//  and because all strings were tested on the same Interface instance. So the later
				//  input strings were not valid, but setValue() would return the current value
				//  which is the same as the expected one since all those strings represent the
				//  same site link.
				//  It is not clear what the definition for valid input is and on which level the
				//  normalization happens (perhaps this test should be moved to EditableSiteLink
				//  level.

				//'site\'s language code': testSite.getLanguageCode(),
				//'site\'s short name': testSite.getShortName(),
				//'site\'s name & language code': testSite.getName() + ' (' + testSite.getLanguageCode() + ')'
			};

			$.each( inputStrings, function( description, inputVal ) {
				var $node = $( '<div/>', { id: 'subject' } ),
					siteIdInterface = newTestSiteIdInterface( $node );

				assert.strictEqual(
					siteIdInterface.setValue( inputVal ),
					testSite.getId(),
					'set value to ' + description + ' "' + inputVal + '"'
				);

				assert.strictEqual(
					siteIdInterface.getSelectedSite(),
					testSite,
					'site (id "' + testSite.getId() + '") selected from input string "' + inputVal + '"'
				);
			} );
		} );
	} );

	QUnit.test( 'invalid input', function( assert ) {
		var $node = $( '<div/>', { id: 'subject' } ),
			siteIdInterface = newTestSiteIdInterface( $node ),
			lastValidSiteId = 'dewiki';

		assert.equal(
			siteIdInterface.setValue( lastValidSiteId ),
			lastValidSiteId,
			'set value to valid value (not null) first'
		);

		var lastValidSite = siteIdInterface.getSelectedSite();

		// NOTE: the tested behavior is whether when entering an invalid value, the previous value
		//       will still be set and returned by setValue(). This is very confusing behavior but
		//       the behavior as specified by wb.ui.PropertyEditTool.EditableValue.Interface

		assert.equal(
			siteIdInterface.setValue( '' ),
			lastValidSiteId,
			'set value to an empty string, invalid, previous valid value will be returned'
		);

		assert.strictEqual(
			siteIdInterface.getSelectedSite(),
			lastValidSite,
			'getSelectedSite() returns same site as before'
		);

		assert.strictEqual(
			siteIdInterface.getSelectedSiteId(),
			lastValidSiteId,
			'getSelectedSiteId() returns previously valid value'
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
			'no site id selected'
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

		// let site selector widget search for any result
		siteIdInterface._inputElem.data( 'siteselector' ).search( wb.getSite( siteIds[1] ).getId() );

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
			wb.getSite( siteIds[0] ).getId(),
			'set value to valid site id'
		);

		assert.equal(
			siteIdInterface.getSelectedSite(),
			wb.getSite( siteIds[0] ),
			'verified selected site'
		);

	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
