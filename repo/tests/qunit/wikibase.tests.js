/**
 * QUnit tests for general wikibase JavaScript code
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
	module( 'wikibase', window.QUnit.newWbEnvironment() );

	test( 'basic', function() {

		expect( 4 );

		ok(
			window.wikibase instanceof Object,
			'initiated wikibase object'
		);

		ok(
			window.wikibase.getSites() instanceof Object,
			'gettings sites resturns object'
		);

		equal(
			window.wikibase.getSite( 'xy' ),
			null,
			'trying to get invalid site returns null'
		);

		equal(
			window.wikibase.hasSite( 'xy' ),
			false,
			'trying to check for invalid site returns false'
		);

	} );

}() );
