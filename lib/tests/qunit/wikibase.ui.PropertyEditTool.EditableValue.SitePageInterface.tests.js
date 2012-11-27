/**
 * QUnit tests for site page interface component of property edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */
'use strict';


( function() {
	module( 'wikibase.ui.PropertyEditTool.EditableValue.SitePageInterface', window.QUnit.newWbEnvironment( {
		setup: function() {
			this.node = $( '<div/>', { id: 'subject' } ).append( $( 'a', { text: 'Link' } ) );
			this.siteDetails = {
				'en': {
					apiUrl: 'http://en.wikipedia.org/w/api.php',
					id: 'en',
					name: 'English Wikipedia',
					pageUrl: 'http://en.wikipedia.org/wiki/$1',
					shortName: 'English'
				},
				'de': {
					apiUrl: 'http://de.wikipedia.org/w/api.php',
					id: 'de',
					name: 'Deutsche Wikipedia',
					pageUrl: 'http://de.wikipedia.org/wiki/$1',
					shortName: 'Deutsch'
				}
			};
			this.sites = {
				'en': new window.wikibase.Site( this.siteDetails.en ),
				'de': new window.wikibase.Site( this.siteDetails.de )
			};
			this.language = {
				rtl: {
					code: 'fakertllang',
					dir: 'rtl'
				},
				ltr: {
					code: 'fakeltrlang',
					dir: 'ltr'
				}
			};
			this.subject = new window.wikibase.ui.PropertyEditTool.EditableValue.SitePageInterface( this.node, {}, this.sites.en );

			ok(
				this.subject.getSubject()[0] == this.node[0],
				'validated subject'
			);

		},
		teardown: function() {
			this.subject.destroy();

			equal(
				this.subject.site,
				null,
				'destroyed object'
			);

			this.subject = null;
			this.siteDetails = null;
			this.sites = null;
		}
	} ) );


	test( 'basic', function() {

		equal(
			this.subject.getSite(),
			this.sites.en,
			'verified site'
		);

		this.subject.setSite( this.sites.de );

		equal(
			this.subject.getSite(),
			this.sites.de,
			'set new site'
		);

	} );


	test( 'update language attributes', function() {

		this.subject.setLanguageAttributes( this.language.ltr );

		equal(
			this.subject.getSubject().attr( 'lang' ),
			this.language.ltr.code,
			'assign ltr language code to subject'
		);

		equal(
			this.subject.getSubject().attr( 'dir' ),
			this.language.ltr.dir,
			'assign ltr language direction to subject'
		);

		equal(
			this.subject.startEditing(),
			true,
			'start editing'
		);

		this.subject.setLanguageAttributes( this.language.rtl );

		equal(
			this.subject._inputElem.data( 'suggester' ).menu.element.attr( 'lang' ),
			this.language.rtl.code,
			'assign rtl language code to auto-complete menu'
		);

		equal(
			this.subject._inputElem.data( 'suggester' ).menu.element.attr( 'dir' ),
			this.language.rtl.dir,
			'assign rtl language direction to auto-complete menu'
		);

	} );


}() );
