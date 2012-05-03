/**
 * QUnit tests for site page interface component of property edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.SitePageInterface.tests.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
'use strict';


( function() {
	module( 'wikibase.ui.PropertyEditTool.EditableValue.SitePageInterface', {
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
			this.subject = new window.wikibase.ui.PropertyEditTool.EditableValue.SitePageInterface( this.node, this.sites.en );
			this.string = 'Test';

			ok(
				this.subject._subject[0] == this.node[0],
				'validated subject'
			);

		},
		teardown: function() {
			this.subject.destroy();

			equal(
				$( this.subject._getValueContainer()[0] ).children().length,
				1,
				'no input element'
			);

			this.string = null;
			this.subject = null;
			this.siteDetails = null;
			this.sites = null;
		}
	} );


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

		this.subject.setValue( this.string );

		equal(
			this.subject.getValue(),
			this.string,
			'set value'
		);

	} );


}() );
