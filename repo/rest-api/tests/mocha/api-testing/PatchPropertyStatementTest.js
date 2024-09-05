'use strict';

const { assert, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const { formatStatementEditSummary } = require( '../helpers/formatEditSummaries' );
const {
	newPatchStatementRequestBuilder,
	newPatchPropertyStatementRequestBuilder,
	newReplaceStatementRequestBuilder,
	newAddPropertyStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );

describe( 'PATCH property statement', () => {
	let testPropertyId;
	let testStatement;
	let testStatementId;
	let previousLastModified;
	let previousEtag;

	before( async function () {
		const testStatementPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
		const addStatementResponse = await newAddPropertyStatementRequestBuilder(
			testPropertyId,
			entityHelper.newStatementWithRandomStringValue( testStatementPropertyId )
		).assertValidRequest().makeRequest();

		expect( addStatementResponse ).to.have.status( 201 );
		testStatement = addStatementResponse.body;
		testStatementId = testStatement.id;

		const testPropertyCreationMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
		previousLastModified = new Date( testPropertyCreationMetadata.timestamp );
		previousEtag = testPropertyCreationMetadata.revid;

		// wait 1s before next test to ensure the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	[
		newPatchStatementRequestBuilder,
		( statementId, patch ) => newPatchPropertyStatementRequestBuilder( testPropertyId, statementId, patch )
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

					const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
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
					const response = await newPatchRequestBuilder( testStatementId.replace( 'P', 'X' ), [] )
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
					const response = await newPatchRequestBuilder( testStatementId, [] )
						.withJsonBodyParam( 'comment', 'x'.repeat( 501 ) )
						.assertValidRequest()
						.makeRequest();

					assertValidError( response, 400, 'value-too-long', { path: '/comment', limit: 500 } );
					assert.strictEqual( response.body.message, 'The input value is too long' );
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

			} );

			describe( '404 statement not found', () => {
				it( 'responds 404 statement not found for nonexistent statement', async () => {
					const statementId = `${testPropertyId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
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

					assertValidError( response, 422, 'patch-result-modified-read-only-value', { path: '/property/id' } );
					assert.strictEqual( response.body.message, 'Read only value in patch result cannot be modified' );
				} );

				it( 'rejects Statement ID change', async () => {
					const patch = [ {
						op: 'replace',
						path: '/id',
						value: `${testPropertyId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`
					} ];
					const response = await newPatchRequestBuilder( testStatementId, patch )
						.assertValidRequest().makeRequest();

					assertValidError( response, 422, 'patch-result-modified-read-only-value', { path: '/id' } );
					assert.strictEqual( response.body.message, 'Read only value in patch result cannot be modified' );
				} );
			} );
		} );

	} );

	describe( 'long route specific errors', () => {

		it( 'responds 400 for invalid property ID', async () => {
			const response = await newPatchPropertyStatementRequestBuilder( 'X123', testStatementId, [] )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'property_id' }
			);
		} );

		it( 'responds 400 if property and statement do not match', async () => {
			const requestedPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			const response = await newPatchPropertyStatementRequestBuilder( requestedPropertyId, testStatementId, [] )
				.assertValidRequest()
				.makeRequest();

			const context = { property_id: requestedPropertyId, statement_id: testStatementId };
			assertValidError( response, 400, 'property-statement-id-mismatch', context );
		} );

		it( 'responds 404 property-not-found for nonexistent property', async () => {
			const propertyId = 'P999999999';
			const statementId = 'P999999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newPatchPropertyStatementRequestBuilder( propertyId, statementId, [] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );

	} );

	describe( 'short route specific errors', () => {
		it( 'responds 400 invalid-statement-id if statement is not on a supported entity type', async () => {
			const response = await newPatchStatementRequestBuilder( testStatementId.replace( 'P', 'L' ), [] )
				.assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'statement_id' }
			);
		} );

		it( 'responds 404 statement not found for nonexistent property', async () => {
			const statementId = 'P999999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newPatchStatementRequestBuilder( statementId, [] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'statement' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );
	} );

} );
