/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, mw, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.RepoApiError' );

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
			error: { messages: { html: { '*': "messages.html['*']" } } }
		} );

		assert.equal(
			error.detailedMessage,
			"messages.html['*']",
			'Non-array-like object structure kept for compatibility reasons'
		);

		error = wb.RepoApiError.newFromApiResponse( 'error-code', {
			error: { messages: { 0: { html: { '*': "messages[0].html['*']" } } } }
		} );

		assert.equal(
			error.detailedMessage,
			"messages[0].html['*']",
			'Array-like object structure with a single message'
		);

		error = wb.RepoApiError.newFromApiResponse( 'error-code', {
			error: { messages: {
				0: { html: { '*': "messages[0].html['*']" } },
				1: { html: { '*': "messages[1].html['*']" } }
			} }
		} );

		assert.equal(
			error.detailedMessage,
			"<ul><li>messages[0].html['*']</li><li>messages[1].html['*']</li></ul>",
			'Array-like object structure with multiple messages'
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

}( wikibase, mediaWiki, QUnit ) );
