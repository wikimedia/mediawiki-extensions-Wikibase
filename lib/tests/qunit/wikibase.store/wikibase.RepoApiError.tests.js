/**
 * QUnit tests for wikibase.RepoApiError
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, $, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.RepoApiError', QUnit.newWbEnvironment() );

	QUnit.test( 'Create and validate errors', function( assert ) {
		var error = new wb.RepoApiError( 'error-code', 'detailed message' );

		assert.equal(
			error.code,
			'error-code',
			'Validated error code.'
		);

		assert.equal(
			error.detailedMessage,
			'detailed message',
			'Validated error message.'
		);

		assert.equal(
			error.message,
			mw.msg( 'wikibase-error-unexpected' ),
			'Unknown error code: Used default generic error message.'
		);

		error = new wb.RepoApiError( 'timeout', 'detailed message', 'remove' );

		assert.equal(
			error.message,
			mw.msg( 'wikibase-error-remove-timeout' ),
			'Picked specific message according to passed "action" parameter.'
		);

		error = new wb.RepoApiError( 'client-error', 'detailed message', 'remove' );

		assert.equal(
			error.message,
			mw.msg( 'wikibase-error-ui-client-error' ),
			'Picked message that has no "action" specific variations regardless of passed "action" '
				+ 'parameter.'
		);

	} );

	QUnit.test( 'Validate errors created via factory method', function( assert ) {
		var error = wb.RepoApiError.newFromApiResponse( 'error-code', {
				error: { info: 'detailed message' }
			} );

		assert.equal(
			error.code,
			'error-code',
			'Created error object via factory method.'
		);

		assert.equal(
			error.detailedMessage,
			'detailed message',
			'Validated detailed message of error created via factory method.'
		);

		error = wb.RepoApiError.newFromApiResponse( 'error-code', {
			textStatus: 'textStatus', exception: 'exception'
		} );

		assert.equal(
			error.code,
			'textStatus',
			'Created error via factory method passing an AJAX exception.'
		);

		assert.equal(
			error.detailedMessage,
			'exception',
			'Validated detailed message of error created via factory method passing an AJAX '
				+ 'exception.'
		);

	} );

}( wikibase, jQuery, QUnit ) );
