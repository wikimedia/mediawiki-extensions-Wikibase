/**
 * QUnit tests for edit group
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */

( function( mw, wb, $, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.ui.Toolbar.Group', QUnit.newWbEnvironment( {
		setup: function() {
			this.subject = new wb.ui.Toolbar.Group();
		},
		teardown: function() {}
	} ) );

	QUnit.test( 'basic', function( assert ) {

		assert.ok(
			this.subject instanceof wb.ui.Toolbar.Group,
			'instantiated group'
		);

		assert.equal(
			this.subject._elem.length,
			1,
			'created DOM structure'
		);

		assert.equal(
			this.subject._elem[0].nodeName,
			'SPAN',
			'child element is a SPAN'
		);

		assert.equal(
			this.subject.renderItemSeparators,
			true,
			'render item separators'
		);

		this.subject.destroy();

		assert.equal(
			this.subject._elem,
			null
		);

	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
