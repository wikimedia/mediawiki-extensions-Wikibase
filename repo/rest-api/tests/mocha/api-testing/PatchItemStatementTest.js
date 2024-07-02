'use strict';

const { assert, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const { formatStatementEditSummary } = require( '../helpers/formatEditSummaries' );
const {
	newAddItemStatementRequestBuilder,
	newPatchItemStatementRequestBuilder,
	newPatchStatementRequestBuilder,
	newReplaceStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( 'PATCH statement tests', () => {
	let testItemId;
	let testStatement;
	let testStatementId;
	let previousLastModified;
	let previousEtag;

	before( async function () {
		testItemId = ( await entityHelper.createItemWithStatements( [] ) ).entity.id;

		const addStatementResponse = await newAddItemStatementRequestBuilder(
			testItemId,
			entityHelper.newStatementWithRandomStringValue(
				( await entityHelper.createUniqueStringProperty() ).entity.id
			)
		).assertValidRequest().makeRequest();

		expect( addStatementResponse ).to.have.status( 201 );
		testStatement = addStatementResponse.body;
		testStatementId = testStatement.id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		previousLastModified = new Date( testItemCreationMetadata.timestamp );
		previousEtag = makeEtag( testItemCreationMetadata.revid );

		// wait 1s before next test to ensure the last-modified timestamps are different
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
				expect( response ).to.have.status( 200 );
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
					expect( response ).to.have.status( 200, errMsg );
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
					const response = await newPatchRequestBuilder( testStatementId.replace( 'Q', 'X' ), [] )
						.assertInvalidRequest().makeRequest();

					assertValidError(
						response,
						400,
						'invalid-path-parameter',
						{ parameter: 'statement_id' }
					);
				} );

				it( 'statement ID is invalid format', async () => {
					const response = await newPatchRequestBuilder( 'not-a-valid-format', [] )
						.assertInvalidRequest().makeRequest();

					assertValidError(
						response,
						400,
						'invalid-path-parameter',
						{ parameter: 'statement_id' }
					);
				} );

				testValidatesPatch( ( patch ) => newPatchRequestBuilder( testStatementId, patch ) );

				it( 'comment too long', async () => {
					const comment = 'x'.repeat( 501 );
					const response = await newPatchRequestBuilder( testStatementId, [] )
						.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

					assertValidError( response, 400, 'comment-too-long' );
					assert.include( response.body.message, '500' );
				} );

				it( 'invalid edit tag', async () => {
					const response = await newPatchRequestBuilder( testStatementId, [] )
						.withJsonBodyParam( 'tags', [ 'invalid tag' ] ).assertValidRequest().makeRequest();

					assertValidError( response, 400, 'invalid-value', { path: '/tags/0' } );
				} );

				it( 'invalid edit tag type', async () => {
					const response = await newPatchRequestBuilder( testStatementId, [] )
						.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

					expect( response ).to.have.status( 400 );
					assert.strictEqual( response.body.code, 'invalid-value' );
					assert.deepEqual( response.body.context, { path: '/tags' } );
				} );

				it( 'invalid bot flag type', async () => {
					const response = await newPatchRequestBuilder( testStatementId, [] )
						.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

					expect( response ).to.have.status( 400 );
					assert.strictEqual( response.body.code, 'invalid-value' );
					assert.deepEqual( response.body.context, { path: '/bot' } );
				} );

				it( 'invalid comment type', async () => {
					const response = await newPatchRequestBuilder( testStatementId, [] )
						.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

					expect( response ).to.have.status( 400 );
					assert.strictEqual( response.body.code, 'invalid-value' );
					assert.deepEqual( response.body.context, { path: '/comment' } );
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

					assertValidError( response, 400, 'invalid-operation-change-property-of-statement' );
				} );

				it( 'rejects Statement ID change', async () => {
					const patch = [ {
						op: 'replace',
						path: '/id',
						value: `${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`
					} ];
					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest().makeRequest();

					assertValidError( response, 400, 'invalid-operation-change-statement-id' );
				} );

			} );

			describe( '404 statement not found', () => {
				it( 'responds 404 statement-not-found for nonexistent statement', async () => {
					const statementId = `${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
					const response = await newPatchRequestBuilder( statementId, [] )
						.assertValidRequest()
						.makeRequest();

					assertValidError( response, 404, 'statement-not-found' );
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

					assertValidError( response, 409, 'patch-target-not-found', { field: 'path', operation } );
					assert.include( response.body.message, operation.path );
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

					assertValidError( response, 409, 'patch-target-not-found', { field: 'from', operation } );
					assert.include( response.body.message, operation.from );
				} );

				it( 'patch test condition failed', async () => {
					const operation = {
						op: 'test',
						path: '/value/content',
						value: { vegetable: 'potato' }
					};
					const response = await newPatchRequestBuilder( testStatementId, [ operation ] )
						.assertValidRequest()
						.makeRequest();

					const context = { 'actual-value': testStatement.value.content, operation };
					assertValidError( response, 409, 'patch-test-failed', context );
					assert.include( response.body.message, 'Test operation in the provided patch failed.' );
					assert.include( response.body.message, operation.path );
					assert.include( response.body.message, JSON.stringify( operation.value ) );
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

					assertValidError( response, 422, 'patched-statement-missing-field', { path: 'value' } );
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

					assertValidError( response, 422, 'patched-statement-invalid-field', { path: 'content', value } );
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

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'item_id' }
			);
		} );

		it( 'responds 400 if item and statement do not match', async () => {
			const requestedItemId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
			const response = await newPatchItemStatementRequestBuilder( requestedItemId, testStatement.id, [] )
				.assertValidRequest()
				.makeRequest();

			const context = { 'item-id': requestedItemId, 'statement-id': testStatement.id };
			assertValidError( response, 400, 'item-statement-id-mismatch', context );
		} );

		it( 'responds 404 item-not-found for nonexistent item', async () => {
			const itemId = 'Q999999999';
			const statementId = itemId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newPatchItemStatementRequestBuilder( itemId, statementId, [] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );

		it( 'responds 404 if statement subject is a redirect', async () => {
			const redirectSource = await entityHelper.createRedirectForItem( testItemId );
			const statementId = redirectSource + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newPatchItemStatementRequestBuilder( redirectSource, statementId, [] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );

	} );

	describe( 'short route specific errors', () => {
		it( 'responds 400 invalid-statement-id if statement is on an unsupported entity', async () => {
			const statementId = testStatementId.replace( 'Q', 'L' );
			const response = await newPatchStatementRequestBuilder( statementId, [] )
				.assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'statement_id' }
			);
		} );

		it( 'responds 404 statement-not-found for nonexistent item', async () => {
			const statementId = 'Q999999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newPatchStatementRequestBuilder( statementId, [] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );

		it( 'responds 404 if statement subject is a redirect', async () => {
			const redirectSource = await entityHelper.createRedirectForItem( testItemId );
			const statementId = redirectSource + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newPatchStatementRequestBuilder( statementId, [] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
	} );

} );
