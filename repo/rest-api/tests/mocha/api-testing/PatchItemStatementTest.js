'use strict';

const { assert, action } = require( 'api-testing' );
const entityHelper = require( '../helpers/entityHelper' );
const hasJsonDiffLib = require( '../helpers/hasJsonDiffLib' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const formatStatementEditSummary = require( '../helpers/formatStatementEditSummary' );

function makeEtag( ...revisionIds ) {
	return revisionIds.map( ( revId ) => `"${revId}"` ).join( ',' );
}

function assertValid400Response( response, responseBodyErrorCode ) {
	assert.strictEqual( response.status, 400 );
	assert.header( response, 'Content-Language', 'en' );
	assert.strictEqual( response.body.code, responseBodyErrorCode );
}

function assertValid404Response( response, responseBodyErrorCode ) {
	assert.strictEqual( response.status, 404 );
	assert.header( response, 'Content-Language', 'en' );
	assert.strictEqual( response.body.code, responseBodyErrorCode );
}

function newPatchStatementRequestBuilder( statementId, patch ) {
	return new RequestBuilder()
		.withRoute( 'PATCH', '/statements/{statement_id}' )
		.withPathParam( 'statement_id', statementId )
		.withJsonBodyParam( 'patch', patch );
}

function newPatchItemStatementRequestBuilder( itemId, statementId, patch ) {
	return new RequestBuilder()
		.withRoute( 'PATCH', '/entities/items/{item_id}/statements/{statement_id}' )
		.withPathParam( 'item_id', itemId )
		.withPathParam( 'statement_id', statementId )
		.withJsonBodyParam( 'patch', patch );
}

describe( 'PATCH statement tests', () => {
	let testItemId;
	let testPropertyId;
	let testStatementId;
	let originalLastModified;
	let originalRevisionId;

	before( async function () {
		if ( !hasJsonDiffLib() ) {
			this.skip(); // awaiting security review (T316245)
		}

		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;

		const createItemResponse = await entityHelper.createItemWithStatements( [
			entityHelper.newStatementWithRandomStringValue( testPropertyId )
		] );
		testItemId = createItemResponse.entity.id;
		testStatementId = createItemResponse.entity.claims[ testPropertyId ][ 0 ].id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		// wait 1s before adding any statements to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	[
		{
			route: 'PATCH /entities/items/{item_id}/statements/{statement_id}',
			newPatchRequestBuilder: ( statementId, patch ) =>
				newPatchItemStatementRequestBuilder( testItemId, statementId, patch )
		},
		{
			route: 'PATCH /statements/{statement_id}',
			newPatchRequestBuilder: newPatchStatementRequestBuilder
		}
	].forEach( ( { route, newPatchRequestBuilder } ) => {
		describe( route, () => {

			function assertValid200Response( response ) {
				assert.strictEqual( response.status, 200 );
				assert.strictEqual( response.body.id, testStatementId );
				assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
				assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
				assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
			}

			describe( '200 success response', () => {

				afterEach( async () => {
					await new RequestBuilder() // reset after successful edit
						.withRoute( 'PUT', '/statements/{statement_id}' )
						.withPathParam( 'statement_id', testStatementId )
						.withJsonBodyParam(
							'statement',
							entityHelper.newStatementWithRandomStringValue( testPropertyId )
						)
						.makeRequest();
				} );

				it( 'can patch a statement', async () => {
					const expectedValue = 'i been patched!!';
					const response = await newPatchRequestBuilder( testStatementId, [
						{
							op: 'replace',
							path: '/mainsnak/datavalue/value',
							value: expectedValue
						}
					] ).assertValidRequest().makeRequest();

					assertValid200Response( response );
					assert.strictEqual( response.body.mainsnak.datavalue.value, expectedValue );
				} );

				it( 'allows content-type application/json-patch+json', async () => {
					const expectedValue = 'i been patched again!!';
					const response = await newPatchRequestBuilder( testStatementId, [
						{
							op: 'replace',
							path: '/mainsnak/datavalue/value',
							value: expectedValue
						}
					] )
						.withHeader( 'content-type', 'application/json-patch+json' )
						.assertValidRequest().makeRequest();

					assertValid200Response( response );
					assert.strictEqual( response.body.mainsnak.datavalue.value, expectedValue );
				} );

				it( 'can patch a statement with edit metadata', async () => {
					const user = await action.mindy();
					const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
					const editSummary = 'i made a patch';
					const expectedValue = `${user.username} was here`;
					const response = await newPatchRequestBuilder( testStatementId, [
						{
							op: 'replace',
							path: '/mainsnak/datavalue/value',
							value: expectedValue
						}
					] ).withJsonBodyParam( 'tags', [ tag ] )
						.withJsonBodyParam( 'bot', true )
						.withJsonBodyParam( 'comment', editSummary )
						.withUser( user )
						.assertValidRequest().makeRequest();

					assertValid200Response( response );
					assert.strictEqual( response.body.mainsnak.datavalue.value, expectedValue );

					const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
					assert.include( editMetadata.tags, tag );
					assert.property( editMetadata, 'bot' );
					assert.strictEqual(
						editMetadata.comment,
						formatStatementEditSummary(
							'wbsetclaim',
							'update',
							response.body.mainsnak,
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
					const invalidPatch = { patch: 'this is not a valid JSON Patch' };
					const response = await newPatchRequestBuilder( testStatementId, invalidPatch )
						.assertInvalidRequest().makeRequest();

					assertValid400Response( response, 'invalid-patch' );
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
					const otherStringProperty = ( await entityHelper.createEntity(
						'property',
						{ datatype: 'string' }
					) ).entity.id;
					const patch = [ {
						op: 'replace',
						path: '/mainsnak/property',
						value: otherStringProperty
					} ];
					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest().makeRequest();
					assert.strictEqual( response.status, 400 );
					assert.strictEqual( response.body.code, 'invalid-operation-change-property-of-statement' );
				} );

				it( 'rejects Statement ID change', async () => {
					const patch = [ {
						op: 'replace',
						path: '/id',
						value: `${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`
					} ];
					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest().makeRequest();
					assert.strictEqual( response.status, 400 );
					assert.strictEqual( response.body.code, 'invalid-operation-change-statement-id' );
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
				it( 'patch cannot be applied', async () => {
					const patch = [ {
						op: 'remove',
						path: '/this/path/does/not/exist'
					} ];
					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest()
						.makeRequest();

					assert.strictEqual( response.statusCode, 409 );
					assert.strictEqual( response.body.code, 'cannot-apply-patch' );
					assert.include( response.body.message, testStatementId );
				} );

				it( 'patch test condition failed', async () => {
					const patch = [ {
						op: 'test',
						path: '/mainsnak/datavalue/value',
						value: 'potato'
					} ];
					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest()
						.makeRequest();

					assert.strictEqual( response.statusCode, 409 );
					assert.strictEqual( response.body.code, 'patch-test-failed' );
				} );
			} );

			describe( '422 Unprocessable Entity', () => {
				it( 'malformed statement serialization', async () => {
					const patch = [ {
						op: 'remove',
						path: '/mainsnak'
					} ];
					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest()
						.makeRequest();

					assert.strictEqual( response.statusCode, 422 );
					assert.strictEqual( response.body.code, 'patched-statement-invalid' );
				} );

				it( 'mismatching value type', async () => {
					const patch = [
						{
							op: 'replace',
							path: '/mainsnak/datavalue',
							value: {
								value: {
									'entity-type': 'item',
									id: testItemId
								},
								type: 'wikibase-entityid'
							}
						}
					];
					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest()
						.makeRequest();

					assert.strictEqual( response.statusCode, 422 );
					assert.strictEqual( response.body.code, 'patched-statement-invalid' );
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
