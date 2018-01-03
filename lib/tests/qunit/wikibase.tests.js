/**
 * QUnit tests for general wikibase JavaScript code
 *
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */

( function ( wb, $, QUnit ) {
	'use strict';

	/**
	 * Place for all test related Objects.
	 * @type Object
	 */
	wb.tests = {};

	QUnit.module( 'wikibase' );

	QUnit.test( 'basic', function ( assert ) {
		assert.ok(
			wb instanceof Object,
			'initiated wikibase object'
		);
	} );

}( wikibase, jQuery, QUnit ) );
