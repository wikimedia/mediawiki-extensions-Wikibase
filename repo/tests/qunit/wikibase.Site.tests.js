/**
 * QUnit tests for site component
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


( function () {
	module( 'wikibase.Site', window.QUnit.newWbEnvironment( {
		setup: function() {
			this.siteDetails = {
				apiUrl: 'http://en.wikipedia.org/w/api.php',
				id: 'en',
				name: 'English Wikipedia',
				pageUrl: 'http://en.wikipedia.org/wiki/$1',
				shortName: 'English'
			};
			this.string = 'test';
			this.site = new window.wikibase.Site( this.siteDetails );

			ok(
				this.site._siteDetails == this.siteDetails,
				'set site details'
			);

		},
		teardown: function() {
			this.site = null;
			this.siteDetails = null;
		}

	} ) );


	test( 'check init', function() {

		ok(
			this.site.getId() == this.siteDetails.id,
			'verified site id'
		);

		ok(
			this.site.getName() == this.siteDetails.name,
			'verified site id'
		);

		ok(
			this.site.getShortName() == this.siteDetails.shortName,
			'verified site id'
		);

		ok(
			this.site.getApi() == this.siteDetails.apiUrl,
			'verified site id'
		);

	} );


	test( 'link handling', function() {

		equal(
			this.site.getLinkTo( this.string )[0].nodeName,
			'A',
			'created DOM node for link'
		);

	} );


}() );
