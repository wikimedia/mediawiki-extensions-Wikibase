/**
 * QUnit tests for Wikibase jQuery ui plugins / helper functions
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
	module( 'wikibase.utilities.jQuery.ui', window.QUnit.newWbEnvironment() );

	test( 'detecting scrollbar width', function() {

		ok(
			$.getScrollbarWidth() > 0,
			'detected scrollbar width'
		);

	} );

}() );
