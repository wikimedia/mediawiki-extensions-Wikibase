/**
 * QUnit tests for site id interface component of property edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
'use strict';


( function() {

	var config = {
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
	};

	module( 'wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface', window.QUnit.newWbEnvironment( {
		config: config,
		setup: function() {
			this.node = $( '<div/>', { id: 'subject' } );
			this.siteIds = ['en', 'de'];
			this.subject = new window.wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface();
		},
		teardown: function() {
			this.subject.destroy();

			equal(
				$( this.subject._getValueContainer()[0] ).children().length,
				0,
				'no input element'
			);

			this.node = null;
			this.subject = null;
		}
	} ) );


	test( 'init input', function() {

		this.subject.init( this.node );

		ok(
			this.subject._subject[0] == this.node[0],
			'validated subject'
		);

		this.subject.startEditing();

		equal(
			this.subject._currentResults.length,
			2,
			'filled result set'
		);

		equal(
			this.subject.getSelectedSiteId(),
			null,
			'no site id selected'
		);

		equal(
			this.subject.setValue( wikibase.getSite( 'en' ).getId() ),
			wikibase.getSite( this.siteIds[0] ).getId(),
			'set value to site id'
		);

		equal(
			this.subject.getSelectedSite(),
			wikibase.getSite( this.siteIds[0] ),
			'verified selected site'
		);

		equal(
			this.subject.setValue( this.subject._currentResults[1].label ),
			this.subject.normalize( this.subject._currentResults[1].label ),
			'set value to input option label'
		);

		equal(
			this.subject.getSelectedSite(),
			wikibase.getSite( this.siteIds[1] ),
			'verified selected site'
		);

		equal(
			this.subject.setValue( this.subject._currentResults[0].value ),
			this.subject.normalize( this.subject._currentResults[0].label ),
			'set value to input option value'
		);

		equal(
			this.subject.getSelectedSite(),
			wikibase.getSite( this.siteIds[0] ),
			'verified selected site'
		);

		equal(
			this.subject.getResultSetMatch( '' ),
			null,
			'get result-set match from empty string'
		);

		var testSite = wikibase.getSite( this.siteIds[0] );
		var testStrings = [
			testSite.getId(),
			testSite.getShortName(),
			testSite.getName() + ' (' + testSite.getId() + ')'
		];

		var self = this;
		$.each( testStrings, function( index, val ) {
			equal(
				self.subject.getResultSetMatch( val ),
				testSite.getId(),
				'get result-set match from string "' + val + '"'
			);
		} );
	} );


	test( 'init input with blacklist', function() {

		this.subject.ignoredSiteLinks = [ wikibase.getSite( this.siteIds[1] ) ];

		this.subject.init( this.node );

		ok(
			this.subject._subject[0] == this.node[0],
			'validated subject'
		);

		this.subject.startEditing();

		equal(
			this.subject._currentResults.length,
			1,
			'filled result set'
		);

		equal(
			this.subject.getSelectedSiteId(),
			null,
			'no site id slected'
		);

		// set this to valid value
		this.subject.setValue( wikibase.getSite( this.siteIds[0] ).getId() ),

		equal(
			this.subject.isValid(),
			true,
			'current value is valid'
		);

		// for next test, do this the hard way since setValue() would reject invalid value (value in blacklist)
		this.subject._inputElem.val( wikibase.getSite( this.siteIds[1] ).getId() )

		equal(
			this.subject.isValid(),
			false,
			'value set to blacklisted site id should be invalid'
		);

		equal(
			this.subject.getSelectedSite(),
			null,
			'no site id selected'
		);

		equal(
			this.subject.setValue( wikibase.getSite( this.siteIds[0] ).getId() ),
			wikibase.getSite( this.siteIds[0] ).getId(),
			'set value to valid site id'
		);

		equal(
			this.subject.getSelectedSite(),
			wikibase.getSite( this.siteIds[0] ),
			'verified selected site'
		);

	} );


}() );
