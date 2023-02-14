'use strict';

const { assert, action } = require( 'api-testing' );
const entityHelper = require( '../helpers/entityHelper' );
const formatStatementEditSummary = require( '../helpers/formatStatementEditSummary' );
const {
	newAddItemStatementRequestBuilder,
	newPatchItemStatementRequestBuilder,
	newPatchStatementRequestBuilder,
	newReplaceStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

function makeEtag( ...revisionIds ) {
	return revisionIds.map( ( revId ) => `"${revId}"` ).join( ',' );
}

function assertValid400Response( response, responseBodyErrorCode, context = null ) {
	assert.strictEqual( response.status, 400 );
	assert.header( response, 'Content-Language', 'en' );
	assert.strictEqual( response.body.code, responseBodyErrorCode );
	if ( context === null ) {
		assert.notProperty( response.body, 'context' );
	} else {
		assert.deepStrictEqual( response.body.context, context );
	}
}

function assertValid404Response( response, responseBodyErrorCode ) {
	assert.strictEqual( response.status, 404 );
	assert.header( response, 'Content-Language', 'en' );
	assert.strictEqual( response.body.code, responseBodyErrorCode );
}

describe( 'PATCH statement tests', () => {
	let testItemId;
	let testPropertyId;
	let testStatement;
	let testStatementId;
	let previousLastModified;
	let previousEtag;

	before( async function () {
		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
		testItemId = ( await entityHelper.createItemWithStatements( [] ) ).entity.id;

		const addStatementResponse = await newAddItemStatementRequestBuilder(
			testItemId,
			entityHelper.newStatementWithRandomStringValue( testPropertyId )
		).assertValidRequest().makeRequest();
		const errMsg = `Add Statement failed with error code: '${addStatementResponse.body.code}'`;
		assert.strictEqual( addStatementResponse.status, 201, errMsg );
		testStatement = addStatementResponse.body;
		testStatementId = testStatement.id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		previousLastModified = new Date( testItemCreationMetadata.timestamp );
		previousEtag = makeEtag( testItemCreationMetadata.revid );

		// wait 1s before adding any statements to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	[
		( statementId, patch ) => newPatchItemStatementRequestBuilder( testItemId, statementId, patch ),
		newPatchStatementRequestBuilder
	].forEach( ( newPatchRequestBuilder ) => {
		describe( newPatchRequestBuilder().getRouteDescription(), () => {

			function assertValid200Response( response ) {
				assert.strictEqual( response.status, 200 );
				assert.strictEqual( response.body.id, testStatementId );
				assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
				assert.isAbove( new Date( response.header[ 'last-modified' ] ), previousLastModified );
				assert.notStrictEqual( response.header.etag, previousEtag );
				previousLastModified = new Date( response.header[ 'last-modified' ] );
				previousEtag = response.header.etag;
			}

			describe( '200 success response', () => {

				afterEach( async () => {
					// reset after each successful edit
					const response = await newReplaceStatementRequestBuilder(
						testStatementId, testStatement
					).makeRequest();
					const errMsg = `Cleanup failed with error code: '${response.body.code}'`;
					assert.strictEqual( response.status, 200, errMsg );
					assert.deepStrictEqual( response.body, testStatement );

					// wait 1s before next test to ensure the last-modified timestamps are different
					await new Promise( ( resolve ) => {
						setTimeout( resolve, 1000 );
					} );
				} );

				it( 'can patch a statement', async () => {
					const expectedValue = 'i been patched!!';
					const response = await newPatchRequestBuilder( testStatementId, [
						{
							op: 'replace',
							path: '/value/content',
							value: expectedValue
						}
					] ).assertValidRequest().makeRequest();

					assertValid200Response( response );
					assert.strictEqual( response.body.value.content, expectedValue );
				} );

				it( 'allows content-type application/json-patch+json', async () => {
					const expectedValue = 'i been patched again!!';
					const response = await newPatchRequestBuilder( testStatementId, [
						{
							op: 'replace',
							path: '/value/content',
							value: expectedValue
						}
					] )
						.withHeader( 'content-type', 'application/json-patch+json' )
						.assertValidRequest().makeRequest();

					assertValid200Response( response );
					assert.strictEqual( response.body.value.content, expectedValue );
				} );

				it( 'can patch a statement with edit metadata', async () => {
					const user = await action.robby(); // robby is a bot
					const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
					const editSummary = 'i made a patch';
					const expectedValue = `${user.username} was here`;
					const response = await newPatchRequestBuilder( testStatementId, [
						{
							op: 'replace',
							path: '/value/content',
							value: expectedValue
						}
					] ).withJsonBodyParam( 'tags', [ tag ] )
						.withJsonBodyParam( 'bot', true )
						.withJsonBodyParam( 'comment', editSummary )
						.withUser( user )
						.assertValidRequest().makeRequest();

					assertValid200Response( response );
					assert.strictEqual( response.body.value.content, expectedValue );

					const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
					assert.include( editMetadata.tags, tag );
					assert.property( editMetadata, 'bot' );
					assert.strictEqual(
						editMetadata.comment,
						formatStatementEditSummary(
							'wbsetclaim',
							'update',
							response.body.property.id,
							response.body.value.content,
							editSummary
						)
					);
					assert.strictEqual( editMetadata.user, user.username );
				} );

			} );

			describe( '400 error response', () => {

				it( 'statement ID contains invalid entity ID', async () => {
					const statementId = testStatementId.replace( 'Q', 'X' );
					const response = await newPatchRequestBuilder( statementId, [] )
						.assertInvalidRequest().makeRequest();

					assertValid400Response( response, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );

				it( 'statement ID is invalid format', async () => {
					const statementId = 'not-a-valid-format';
					const response = await newPatchRequestBuilder( statementId, [] )
						.assertInvalidRequest().makeRequest();

					assertValid400Response( response, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );

				it( 'statement is not on an item', async () => {
					const statementId = testStatementId.replace( 'Q', 'P' );
					const response = await newPatchRequestBuilder( statementId, [] )
						.assertValidRequest().makeRequest();

					assertValid400Response( response, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );

				it( 'comment too long', async () => {
					const comment = 'x'.repeat( 501 );
					const response = await newPatchRequestBuilder( testStatementId, [] )
						.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

					assertValid400Response( response, 'comment-too-long' );
					assert.include( response.body.message, '500' );
				} );

				it( 'invalid patch', async () => {
					const invalidPatch = { foo: 'this is not a valid JSON Patch' };
					const response = await newPatchRequestBuilder( testStatementId, invalidPatch )
						.assertInvalidRequest().makeRequest();

					assertValid400Response( response, 'invalid-patch' );
				} );

				it( "invalid patch - missing 'op' field", async () => {
					const invalidOperation = { path: '/a/b/c', value: 'test' };
					const response = await newPatchRequestBuilder( testStatementId, [ invalidOperation ] )
						.assertInvalidRequest().makeRequest();

					assertValid400Response(
						response,
						'missing-json-patch-field',
						{ operation: invalidOperation, field: 'op' } );
					assert.include( response.body.message, "'op'" );
				} );

				it( "invalid patch - missing 'path' field", async () => {
					const invalidOperation = { op: 'remove' };
					const response = await newPatchRequestBuilder( testStatementId, [ invalidOperation ] )
						.assertInvalidRequest().makeRequest();
					assertValid400Response(
						response,
						'missing-json-patch-field',
						{ operation: invalidOperation, field: 'path' }
					);
					assert.include( response.body.message, "'path'" );
				} );

				it( "invalid patch - missing 'value' field", async () => {
					const invalidOperation = { op: 'add', path: '/a/b/c' };
					const response = await newPatchRequestBuilder( testStatementId, [ invalidOperation ] )
						.makeRequest();

					assertValid400Response(
						response,
						'missing-json-patch-field',
						{ operation: invalidOperation, field: 'value' }
					);
					assert.include( response.body.message, "'value'" );
				} );

				it( "invalid patch - missing 'from' field", async () => {
					const invalidOperation = { op: 'move', path: '/a/b/c' };
					const response = await newPatchRequestBuilder( testStatementId, [ invalidOperation ] )
						.makeRequest();

					assertValid400Response(
						response,
						'missing-json-patch-field',
						{ operation: invalidOperation, field: 'from' }
					);
					assert.include( response.body.message, "'from'" );
				} );

				it( "invalid patch - invalid 'op' field", async () => {
					const invalidOperation = { op: 'foobar', path: '/a/b/c', value: 'test' };
					const response = await newPatchRequestBuilder( testStatementId, [ invalidOperation ] )
						.assertInvalidRequest().makeRequest();

					assertValid400Response( response, 'invalid-patch-operation', { operation: invalidOperation } );
					assert.include( response.body.message, "'foobar'" );
				} );

				it( "invalid patch - 'op' is not a string", async () => {
					const invalidOperation = { op: { foo: [ 'bar' ], baz: 42 }, path: '/a/b/c', value: 'test' };
					const response = await newPatchRequestBuilder( testStatementId, [ invalidOperation ] )
						.assertInvalidRequest().makeRequest();

					assertValid400Response(
						response,
						'invalid-patch-field-type',
						{ operation: invalidOperation, field: 'op' }
					);
					assert.include( response.body.message, "'op'" );
					assert.deepEqual( response.body.context.operation, invalidOperation );
					assert.strictEqual( response.body.context.field, 'op' );
				} );

				it( "invalid patch - 'path' is not a string", async () => {
					const invalidOperation = { op: 'add', path: { foo: [ 'bar' ], baz: 42 }, value: 'test' };
					const response = await newPatchRequestBuilder( testStatementId, [ invalidOperation ] )
						.assertInvalidRequest().makeRequest();

					assertValid400Response(
						response,
						'invalid-patch-field-type',
						{ operation: invalidOperation, field: 'path' }
					);
					assert.include( response.body.message, "'path'" );
				} );

				it( "invalid patch - 'from' is not a string", async () => {
					const invalidOperation = { op: 'move', from: { foo: [ 'bar' ], baz: 42 }, path: '/a/b/c' };
					const response = await newPatchRequestBuilder( testStatementId, [ invalidOperation ] )
						.makeRequest();

					assertValid400Response(
						response,
						'invalid-patch-field-type',
						{ operation: invalidOperation, field: 'from' }
					);
					assert.include( response.body.message, "'from'" );
				} );

				it( 'invalid edit tag', async () => {
					const invalidEditTag = 'invalid tag';
					const response = await newPatchRequestBuilder( testStatementId, [] )
						.withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

					assertValid400Response( response, 'invalid-edit-tag' );
					assert.include( response.body.message, invalidEditTag );
				} );

				it( 'invalid edit tag type', async () => {
					const response = await newPatchRequestBuilder( testStatementId, [] )
						.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

					assert.strictEqual( response.status, 400 );
					assert.strictEqual( response.body.code, 'invalid-request-body' );
					assert.strictEqual( response.body.fieldName, 'tags' );
					assert.strictEqual( response.body.expectedType, 'array' );
				} );

				it( 'invalid bot flag type', async () => {
					const response = await newPatchRequestBuilder( testStatementId, [] )
						.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

					assert.strictEqual( response.status, 400 );
					assert.strictEqual( response.body.code, 'invalid-request-body' );
					assert.strictEqual( response.body.fieldName, 'bot' );
					assert.strictEqual( response.body.expectedType, 'boolean' );
				} );

				it( 'invalid comment type', async () => {
					const response = await newPatchRequestBuilder( testStatementId, [] )
						.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

					assert.strictEqual( response.status, 400 );
					assert.strictEqual( response.body.code, 'invalid-request-body' );
					assert.strictEqual( response.body.fieldName, 'comment' );
					assert.strictEqual( response.body.expectedType, 'string' );
				} );

				it( 'rejects Property ID change', async () => {
					const otherStringPropertyId = ( await entityHelper.createEntity(
						'property',
						{ datatype: 'string' }
					) ).entity.id;
					const patch = [ {
						op: 'replace',
						path: '/property/id',
						value: otherStringPropertyId
					} ];
					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest().makeRequest();

					assertValid400Response( response, 'invalid-operation-change-property-of-statement' );
				} );

				it( 'rejects Statement ID change', async () => {
					const patch = [ {
						op: 'replace',
						path: '/id',
						value: `${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`
					} ];
					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest().makeRequest();

					assertValid400Response( response, 'invalid-operation-change-statement-id' );
				} );

			} );

			describe( '404 statement not found', () => {
				it( 'responds 404 statement-not-found for nonexistent statement', async () => {
					const statementId = `${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
					const response = await newPatchRequestBuilder( statementId, [] )
						.assertValidRequest()
						.makeRequest();

					assertValid404Response( response, 'statement-not-found' );
					assert.include( response.body.message, statementId );
				} );
			} );

			describe( '409 conflict', () => {
				it( '"path" field target does not exist', async () => {
					const operation = {
						op: 'remove',
						path: '/this/path/does/not/exist'
					};
					const response = await newPatchRequestBuilder( testStatementId, [ operation ] )
						.assertValidRequest()
						.makeRequest();

					assert.strictEqual( response.statusCode, 409 );
					assert.strictEqual( response.body.code, 'patch-target-not-found' );
					assert.include( response.body.message, operation.path );
					assert.strictEqual( response.body.context.field, 'path' );
					assert.deepEqual( response.body.context.operation, operation );
				} );

				it( '"from" field target does not exist', async () => {
					const operation = {
						op: 'move',
						from: '/this/path/does/not/exist',
						path: '/somewhere'
					};
					const response = await newPatchRequestBuilder( testStatementId, [ operation ] )
						.assertValidRequest()
						.makeRequest();

					assert.strictEqual( response.statusCode, 409 );
					assert.strictEqual( response.body.code, 'patch-target-not-found' );
					assert.include( response.body.message, operation.from );
					assert.strictEqual( response.body.context.field, 'from' );
					assert.deepEqual( response.body.context.operation, operation );
				} );

				it( 'patch test condition failed', async () => {
					const patchOperation = {
						op: 'test',
						path: '/value/content',
						value: { vegetable: 'potato' }
					};
					const response = await newPatchRequestBuilder( testStatementId, [ patchOperation ] )
						.assertValidRequest()
						.makeRequest();

					assert.strictEqual( response.statusCode, 409 );

					assert.strictEqual( response.body.code, 'patch-test-failed' );
					assert.deepEqual( response.body.context.operation, patchOperation );
					assert.deepEqual( response.body.context[ 'actual-value' ], testStatement.value.content );
					assert.include( response.body.message, 'Test operation in the provided patch failed.' );
					assert.include( response.body.message, patchOperation.path );
					assert.include( response.body.message, JSON.stringify( patchOperation.value ) );
					assert.include( response.body.message, testStatement.value.content );
				} );
			} );

			describe( '422 Unprocessable Entity', () => {
				it( 'malformed statement serialization', async () => {
					const patch = [ {
						op: 'remove',
						path: '/value'
					} ];
					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest()
						.makeRequest();

					assert.strictEqual( response.statusCode, 422 );
					assert.strictEqual( response.body.code, 'patched-statement-missing-field' );
					assert.strictEqual( response.body.context.path, 'value' );
				} );

				it( 'incorrect value type', async () => {
					const value = {
						content: {
							amount: '+10.38',
							upperBound: '+10.385',
							lowerBound: '+10.375',
							unit: 'http://www.wikidata.org/entity/Q712226'
						},
						type: 'value'
					};
					const patch = [
						{ op: 'replace', path: '/value/content', value }
					];

					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest()
						.makeRequest();

					assert.strictEqual( response.statusCode, 422 );
					const body = response.body;
					assert.strictEqual( body.code, 'patched-statement-invalid-field' );
					assert.deepEqual( body.context, { path: 'content', value } );
				} );
			} );

		} );

	} );

	describe( 'long route specific errors', () => {

		it( 'responds 400 for invalid item ID', async () => {
			const itemId = 'X123';
			const response = await newPatchItemStatementRequestBuilder( itemId, testStatementId, [] )
				.assertInvalidRequest()
				.makeRequest();

			assertValid400Response( response, 'invalid-item-id' );
			assert.include( response.body.message, itemId );
		} );

		it( 'responds 404 item-not-found for nonexistent item', async () => {
			const itemId = 'Q999999999';
			const response = await newPatchItemStatementRequestBuilder( itemId, testStatementId, [] )
				.assertValidRequest()
				.makeRequest();

			assertValid404Response( response, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );

	} );

	describe( 'short route specific errors', () => {
		it( 'responds 404 statement-not-found for nonexistent item', async () => {
			const statementId = 'Q999999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newPatchStatementRequestBuilder( statementId, [] )
				.assertValidRequest()
				.makeRequest();

			assertValid404Response( response, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
	} );

} );
