'use strict';

const { assert, action } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const { formatStatementEditSummary } = require( '../helpers/formatEditSummaries' );
const {
	newPatchItemStatementRequestBuilder,
	newPatchStatementRequestBuilder,
	newReplaceStatementRequestBuilder,
	newCreateItemRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );

describe( 'PATCH statement tests', () => {
	let testItemId;
	let testStatement;
	let testStatementId;
	let previousLastModified;
	let previousEtag;

	before( async function () {
		const propertyId = await entityHelper.getStringPropertyId();
		const item = await entityHelper.createItemWithStatements( [
			entityHelper.newStatementWithRandomStringValue( propertyId )
		] );

		testItemId = item.id;
		testStatement = item.statements[ propertyId ][ 0 ];
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

				it( 'can patch a statement with edit metadata', async () => {
					const user = await getOrCreateBotUser();
					const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
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
			} );

			describe( '404 statement not found', () => {
				it( 'responds 404 statement not found for nonexistent statement', async () => {
					const statementId = `${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
					const response = await newPatchRequestBuilder( statementId, [] )
						.assertValidRequest()
						.makeRequest();

					assertValidError( response, 404, 'resource-not-found', { resource_type: 'statement' } );
					assert.strictEqual( response.body.message, 'The requested resource does not exist' );
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

					const context = { path: '/patch/0/path' };
					assertValidError( response, 409, 'patch-target-not-found', context );
					assert.strictEqual( response.body.message, 'Target not found on resource' );
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

					const context = { path: '/patch/0/from' };
					assertValidError( response, 409, 'patch-target-not-found', context );
					assert.strictEqual( response.body.message, 'Target not found on resource' );
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

					const context = { path: '/patch/0', actual_value: testStatement.value.content };
					assertValidError( response, 409, 'patch-test-failed', context );
					assert.strictEqual( response.body.message, 'Test operation in the provided patch failed' );
				} );
			} );

			describe( '422 Unprocessable Entity', () => {
				it( 'invalid statement - string', async () => {
					const patch = [
						{ op: 'replace', path: '', value: '' }
					];

					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest()
						.makeRequest();

					assertValidError( response, 422, 'patch-result-invalid-value', { path: '', value: '' } );
				} );

				it( 'invalid statement - array', async () => {
					const value = [ 'not', 'an', 'associative', 'array' ];
					const patch = [
						{ op: 'replace', path: '', value }
					];

					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest()
						.makeRequest();

					assertValidError( response, 422, 'patch-result-invalid-value', { path: '', value } );
				} );

				it( 'malformed statement serialization', async () => {
					const patch = [ {
						op: 'remove',
						path: '/value'
					} ];
					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest()
						.makeRequest();

					const context = { path: '', field: 'value' };
					assertValidError( response, 422, 'patch-result-missing-field', context );
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

					assertValidError( response, 422, 'patch-result-invalid-value', { path: '/value/content', value } );
				} );

				it( 'rejects Property ID change', async () => {
					const otherStringPropertyId = ( await newCreatePropertyRequestBuilder( { data_type: 'string' } )
						.makeRequest() ).body.id;
					const patch = [ {
						op: 'replace',
						path: '/property/id',
						value: otherStringPropertyId
					} ];
					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest().makeRequest();

					assertValidError( response, 422, 'patch-result-modified-read-only-value', { path: '/property/id' } );
					assert.strictEqual( response.body.message, 'Read only value in patch result cannot be modified' );
				} );

				it( 'rejects Statement ID change', async () => {
					const patch = [ {
						op: 'replace',
						path: '/id',
						value: `${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`
					} ];
					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest().makeRequest();

					assertValidError( response, 422, 'patch-result-modified-read-only-value', { path: '/id' } );
					assert.strictEqual( response.body.message, 'Read only value in patch result cannot be modified' );
				} );
			} );

			it( 'rejects qualifier with non-existent property', async () => {
				const nonExistentProperty = 'P9999999';
				const patch = [ {
					op: 'add',
					path: '/qualifiers',
					value: [ { property: { id: nonExistentProperty }, value: { type: 'novalue' } } ]
				} ];
				const response = await newPatchRequestBuilder( testStatementId, patch )
					.assertValidRequest().makeRequest();

				assertValidError(
					response,
					422,
					'patch-result-referenced-resource-not-found',
					{ path: '/qualifiers/0/property/id', value: nonExistentProperty }
				);
				assert.strictEqual( response.body.message, 'The referenced resource does not exist' );
			} );

			it( 'rejects reference with non-existent property', async () => {
				const nonExistentProperty = 'P9999999';
				const patch = [ {
					op: 'add',
					path: '/references/0',
					value: { parts: [ { property: { id: nonExistentProperty }, value: { type: 'novalue' } } ] }
				} ];
				const response = await newPatchRequestBuilder( testStatementId, patch )
					.assertValidRequest().makeRequest();

				assertValidError(
					response,
					422,
					'patch-result-referenced-resource-not-found',
					{ path: '/references/0/parts/0/property/id', value: nonExistentProperty }
				);
				assert.strictEqual( response.body.message, 'The referenced resource does not exist' );
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
			const requestedItemId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
			const response = await newPatchItemStatementRequestBuilder( requestedItemId, testStatement.id, [] )
				.assertValidRequest()
				.makeRequest();

			const context = { item_id: requestedItemId, statement_id: testStatement.id };
			assertValidError( response, 400, 'item-statement-id-mismatch', context );
		} );

		it( 'responds 404 item-not-found for nonexistent item', async () => {
			const itemId = 'Q999999999';
			const statementId = itemId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newPatchItemStatementRequestBuilder( itemId, statementId, [] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'item' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );

		it( 'responds 404 if statement subject is a redirect', async () => {
			const redirectSource = await entityHelper.createRedirectForItem( testItemId );
			const statementId = redirectSource + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newPatchItemStatementRequestBuilder( redirectSource, statementId, [] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'statement' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
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

		it( 'responds 404 statement not found for nonexistent item', async () => {
			const statementId = 'Q999999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newPatchStatementRequestBuilder( statementId, [] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'statement' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );

		it( 'responds 404 if statement subject is a redirect', async () => {
			const redirectSource = await entityHelper.createRedirectForItem( testItemId );
			const statementId = redirectSource + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newPatchStatementRequestBuilder( statementId, [] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'statement' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );
	} );

} );
