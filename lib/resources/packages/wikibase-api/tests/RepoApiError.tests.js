/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */

( function ( wb, QUnit ) {
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
			mw.msg( 'wikibase-error-unexpected' ),
			'Unknown error code: Used default generic error message.'
		);

		error = new wb.api.RepoApiError( 'timeout', 'detailed message', [], 'remove' );

		assert.strictEqual(
			error.message,
			mw.msg( 'wikibase-error-remove-timeout' ),
			'Picked specific message according to passed "action" parameter.'
		);

	} );

	QUnit.test( 'Validate errors created via factory method', function ( assert ) {
		var error = wb.api.RepoApiError.newFromApiResponse( {
			error: { code: 'error-code', info: 'detailed message' }
		} );

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
				messages: { 0: { html: { '*': "messages[0].html['*']" } } }
			}
		} );

		assert.strictEqual(
			error.detailedMessage,
			"messages[0].html['*']",
			'Array-like object structure with a single message'
		);

		error = wb.api.RepoApiError.newFromApiResponse( {
			error: { code: 'error-code', messages: {
				0: { html: { '*': "messages[0].html['*']" } },
				1: { html: { '*': "messages[1].html['*']" } }
			} }
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

		error = wb.api.RepoApiError.newFromApiResponse(
			{ errors: [ { code: 'error-code', module: 'main', '*': 'detailed message' } ] },
			'wbaction'
		);

		assert.strictEqual(
			error.code,
			'error-code',
			'Created error object via factory method from a list of errors.'
		);

		assert.strictEqual(
			error.detailedMessage,
			'detailed message',
			'Validated detailed message of error created via factory method from a list of errors.'
		);

		assert.strictEqual(
			error.action,
			'wbaction',
			'Validated API action of error created via factory method from a list of errors.'
		);
	} );

	QUnit.test( 'Validate param-illegal error from recorded API response', function ( assert ) {
		var error = wb.api.RepoApiError.newFromApiResponse(
			JSON.parse( '{' +
				'  "errors": [' +
				'    {' +
				'      "code": "param-illegal",' +
				'      "data": {' +
				'        "messages": [' +
				'          {' +
				'            "name": "wikibase-api-illegal-id-or-site-page-selector",' +
				'            "parameters": [],' +
				'            "html": {' +
				'              "*": "You need to provide either an entity <var>id</var>, or a <var>site</var> and <var>page</var> combination, but not both."' +
				'            }' +
				'          }' +
				'        ]' +
				'      },' +
				'      "module": "main",' +
				'      "*": "You need to provide either an entity \\"id\\", or a \\"site\\" and \\"page\\" combination, but not both."' +
				'    }' +
				'  ]}' ),
			'wbeditentity'
		);

		assert.strictEqual( error.code, 'param-illegal', 'Created param-illegal error.' );
		assert.strictEqual(
			error.detailedMessage,
			// eslint-disable-next-line no-useless-escape
			'You need to provide either an entity \"id\", or a \"site\" and \"page\" combination, but not both.',
			'Validated param-illegal message.' );
		assert.strictEqual( error.action, 'wbeditentity', 'Validated wbeditentity action.' );
	} );

	QUnit.test( 'Validate nodata error from recorded API response', function ( assert ) {
		var error = wb.api.RepoApiError.newFromApiResponse(
			JSON.parse( '{' +
				'  "errors": [' +
				'    {' +
				'      "code": "nodata",' +
				'      "module": "wbeditentity",' +
				'      "*": "The \\"data\\" parameter must be set."' +
				'    }' +
				'  ]}' ),
			'wbeditentity'
		);

		assert.strictEqual( error.code, 'nodata', 'Created nodata error.' );
		assert.strictEqual(
			error.detailedMessage,
			// eslint-disable-next-line no-useless-escape
			'The \"data\" parameter must be set.',
			'Validated nodata message.' );
		assert.strictEqual( error.action, 'wbeditentity', 'Validated wbeditentity action.' );
	} );

	QUnit.test( 'Validate editconflict error from recorded API response', function ( assert ) {
		var error = wb.api.RepoApiError.newFromApiResponse(
			JSON.parse( '{' +
				'  "errors": [' +
				'    {' +
				'      "code": "editconflict",' +
				'      "module": "wbsetdescription",' +
				'      "*": "Edit conflict. Could not patch the current revision."' +
				'    },' +
				'    {' +
				'      "code": "editconflict",' +
				'      "module": "wbsetdescription",' +
				'      "*": "Bearbeitungskonflikt."' +
				'    },' +
				'    {' +
				'      "code": "editconflict",' +
				'      "data": {' +
				'        "messages": [' +
				'          {' +
				'            "name": "wikibase-api-editconflict",' +
				'            "parameters": [],' +
				'            "html": {' +
				'              "*": "Edit conflict. Could not patch the current revision."' +
				'            }' +
				'          },' +
				'          {' +
				'            "name": "edit-conflict",' +
				'            "parameters": [],' +
				'            "html": {' +
				'              "*": "Bearbeitungskonflikt."' +
				'            }' +
				'          }' +
				'        ]' +
				'      },' +
				'      "module": "wbsetdescription",' +
				'      "*": "Edit conflict. Could not patch the current revision."' +
				'    }' +
				'  ]}' ),
			'wbeditentity'
		);

		assert.strictEqual( error.code, 'editconflict', 'Created editconflict error.' );
		assert.strictEqual(
			error.detailedMessage,
			'Edit conflict. Could not patch the current revision.',
			'Validated editconflict message.' );
		assert.strictEqual( error.action, 'wbeditentity', 'Validated wbeditentity action.' );
	} );
}( wikibase, QUnit ) );
