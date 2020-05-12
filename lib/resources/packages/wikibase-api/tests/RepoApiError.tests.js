/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */

( function ( wb, QUnit, sinon ) {
	'use strict';

	QUnit.module( 'wikibase.api.RepoApiError' );

	QUnit.test( 'Create and validate errors', function ( assert ) {
		var error = new wb.api.RepoApiError( 'error-code', 'detailed message' );

		assert.strictEqual(
			error.code,
			'error-code',
			'Validated error code.'
		);

		assert.strictEqual(
			error.detailedMessage,
			'detailed message',
			'Validated error message.'
		);

		assert.strictEqual(
			error.message,
			mw.msg( 'wikibase-error-unknown' ),
			'Unknown error code: Used default generic unknown error message.'
		);

		// Check generic error message with parameters
		error = new wb.api.RepoApiError( 'error-code', 'detailed message', [ 'mock parameter' ] );

		assert.strictEqual(
			error.message,
			mw.msg( 'wikibase-error-unexpected' ),
			'Unexpected error code: Used default generic error message with parameters.'
		);

		error = new wb.api.RepoApiError( 'timeout', 'detailed message', [], 'remove' );

		assert.strictEqual(
			error.message,
			mw.msg( 'wikibase-error-remove-timeout' ),
			'Picked specific message according to passed "action" parameter.'
		);

	} );

	QUnit.test( 'Validate errors created via factory method, requested with unspecified errorformat',
		function ( assert ) {
			var error = wb.api.RepoApiError.newFromApiResponse(
				{ error: { code: 'error-code', info: 'detailed message' } },
				'wbaction'
			);

			assert.strictEqual(
				error.code,
				'error-code',
				'Created error object via factory method.'
			);

			assert.strictEqual(
				error.detailedMessage,
				'detailed message',
				'Validated detailed message of error created via factory method.'
			);

			assert.strictEqual(
				error.action,
				'wbaction',
				'Validated API action'
			);

			error = wb.api.RepoApiError.newFromApiResponse( {
				error: { code: 'error-code', messages: { html: { '*': "messages.html['*']" } } }
			} );

			assert.strictEqual(
				error.detailedMessage,
				"messages.html['*']",
				'Non-array-like object structure kept for compatibility reasons'
			);

			error = wb.api.RepoApiError.newFromApiResponse( {
				error: {
					code: 'error-code',
					messages: [ { html: { '*': "messages[0].html['*']" } } ]
				}
			} );

			assert.strictEqual(
				error.detailedMessage,
				"messages[0].html['*']",
				'Array-like object structure with a single message'
			);

			error = wb.api.RepoApiError.newFromApiResponse( {
				error: { code: 'error-code', messages: [
					{ html: { '*': "messages[0].html['*']" } },
					{ html: { '*': "messages[1].html['*']" } }
				] }
			} );

			assert.strictEqual(
				error.detailedMessage,
				"<ul><li>messages[0].html['*']</li><li>messages[1].html['*']</li></ul>",
				'Array-like object structure with multiple messages'
			);

			error = wb.api.RepoApiError.newFromApiResponse( {
				textStatus: 'textStatus', exception: 'exception'
			} );

			assert.strictEqual(
				error.code,
				'textStatus',
				'Created error via factory method passing an AJAX exception.'
			);

			assert.strictEqual(
				error.detailedMessage,
				'exception',
				'Validated detailed message of error created via factory method passing an AJAX '
				+ 'exception.'
			);
		} );

	QUnit.test( 'Validate parameterised message for API response, requested with unspecified errorformat',
		function ( assert ) {
			var expectedMessageKey = 'wikibase-error-ui-no-external-page',
				messageParams = [ 'external-client-parameter', 'page-parameter' ],
				expectedMessage = 'some formatted error message with parameters',
				mwMsgMock = sinon.stub( mw, 'msg' ).returns( expectedMessage ),
				error = wb.api.RepoApiError.newFromApiResponse(
					{ error: {
						code: 'no-external-page',
						messages: [ {
							parameters: messageParams
						} ]
					}
					},
					'wbeditentity'
				);

			assert.ok(
				mwMsgMock.calledWith( expectedMessageKey, messageParams[ 0 ], messageParams[ 1 ] ),
				'Called mw.msg with the correct msgKey and parameters to build the error message.'
			);
			assert.strictEqual(
				error.message,
				expectedMessage
			);

			mwMsgMock.restore();
		} );

	QUnit.test( 'Validate errors created via factory method, requested with errorformat=plaintext',
		function ( assert ) {
			var error = wb.api.RepoApiError.newFromApiResponse( {
				errors: [ { code: 'error-code', '*': 'detailed message' } ]
			}
			);

			assert.strictEqual(
				error.code,
				'error-code',
				'Created error object via factory method.'
			);

			assert.strictEqual(
				error.detailedMessage,
				'detailed message',
				'Validated detailed message of error created via factory method.'
			);

			error = wb.api.RepoApiError.newFromApiResponse( {
				errors: [ {
					code: 'error-code',
					data: { messages: { html: { '*': "messages.html['*']" } } }
				} ]
			}
			);

			assert.strictEqual(
				error.detailedMessage,
				"messages.html['*']",
				'Non-array-like object structure kept for compatibility reasons'
			);

			error = wb.api.RepoApiError.newFromApiResponse( {
				errors: [
					{
						code: 'error-code',
						'*': 'This is not very nice and will be ignored in favour of the next error.'
					},
					{
						code: 'error-code',
						data: { messages: [
							{ html: { '*': "messages[0].html['*']" } }
						] }
					}
				]
			} );

			assert.strictEqual(
				error.detailedMessage,
				"messages[0].html['*']",
				'Array-like object structure with a single message'
			);

			error = wb.api.RepoApiError.newFromApiResponse( {
				errors: [
					{
						code: 'error-code',
						data: { messages: [
							{ html: { '*': "messages[0].html['*']" } },
							{ html: { '*': "messages[1].html['*']" } }
						] }
					}
				]
			} );

			assert.strictEqual(
				error.detailedMessage,
				"<ul><li>messages[0].html['*']</li><li>messages[1].html['*']</li></ul>",
				'Array-like object structure with multiple messages'
			);

			error = wb.api.RepoApiError.newFromApiResponse( {
				textStatus: 'textStatus', exception: 'exception'
			} );

			assert.strictEqual(
				error.code,
				'textStatus',
				'Created error via factory method passing an AJAX exception.'
			);

			assert.strictEqual(
				error.detailedMessage,
				'exception',
				'Validated detailed message of error created via factory method passing an AJAX '
				+ 'exception.'
			);
		} );

	QUnit.test( 'Validate parameterised message for API response, requested with `errorformat=plaintext`',
		function ( assert ) {
			var expectedMessageKey = 'wikibase-error-ui-no-external-page',
				messageParams = [ 'external-client-parameter', 'page-parameter' ],
				expectedMessage = 'some formatted error message with parameters',
				mwMsgMock = sinon.stub( mw, 'msg' ).returns( expectedMessage ),
				error = wb.api.RepoApiError.newFromApiResponse(
					{ errors: [ {
						code: 'no-external-page',
						data: {
							messages: [ {
								parameters: messageParams
							} ]
						}
					} ]
					},
					'wbeditentity'
				);

			assert.ok(
				mwMsgMock.calledWith( expectedMessageKey, messageParams[ 0 ], messageParams[ 1 ] ),
				'calls mw.msg with the correct parameters to build the error message'
			);
			assert.strictEqual(
				error.message,
				expectedMessage
			);

			mwMsgMock.restore();
		} );
}( wikibase, QUnit, sinon ) );
