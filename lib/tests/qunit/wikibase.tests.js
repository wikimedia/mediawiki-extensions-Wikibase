/**
 * QUnit tests for general wikibase JavaScript code
 *
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( wb, $, QUnit ) {
	'use strict';

	/**
	 * Place for all test related Objects.
	 * @type Object
	 */
	wb.tests = {};

	QUnit.module( 'wikibase' );

	QUnit.test( 'basic', 1, function( assert ) {
		assert.ok(
			wb instanceof Object,
			'initiated wikibase object'
		);
	} );

}( wikibase, jQuery, QUnit ) );
