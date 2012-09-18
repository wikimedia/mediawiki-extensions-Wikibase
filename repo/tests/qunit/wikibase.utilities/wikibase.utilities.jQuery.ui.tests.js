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

( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.utilities.jQuery.ui', QUnit.newWbEnvironment( {
		setup: function() {},
		teardown: function() {}
	} ) );

	QUnit.test( '$.getScrollbarWidth()', function( assert ) {
		assert.ok(
			$.getScrollbarWidth() > 0,
			'detected scrollbar width'
		);
	} );

	QUnit.test( '$.getInputEvent()', function( assert ) {
		assert.ok(
			$.getInputEvent() ===
				'input' ||
				'input keyup' ||
				'keyup keydown blur cut paste mousedown mouseup mouseout',
			'$.getInputEvent()'
		);
	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
