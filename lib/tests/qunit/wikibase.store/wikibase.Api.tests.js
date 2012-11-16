/**
 * QUnit tests for wkibase.Api
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, $, QUnit, undefined ) {
	'use strict';

	var api = new wb.Api();

	QUnit.module( 'wikibase.Api', QUnit.newWbEnvironment() );

	QUnit.asyncTest( 'Create an entity', function( assert ) {

		api.editEntity().done( function( response ) {
			assert.equal(
				typeof response,
				'object',
				'Response is an object.'
			);

			assert.equal(
				response.success,
				1,
				'Operation was successful.'
			);

			QUnit.start();
		} )
		.fail( function( code, details ) {
			assert.ok( false, 'API request failed returning code: "' + code + '". See console for details.' );
			QUnit.start();
		} );

	} );

}( wikibase, jQuery, QUnit ) );
