/**
 * QUnit tests for template engine.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */

( function( mw, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'templates', QUnit.newMwEnvironment() );

	QUnit.test( 'basic', function( assert ) {

		assert.equal(
			typeof mw.templates,
			'object',
			'mw.templates is defined.'
		);

		assert.equal(
			mw.template( 'wb-editsection-button', ['text', 'link'] ),
			'<a href="link" class="wb-ui-toolbar-button">text</a>',
			'Getting and filling a specific template.'
		);

	} );

}( mediaWiki, jQuery, QUnit ) );
