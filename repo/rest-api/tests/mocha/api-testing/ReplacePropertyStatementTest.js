'use strict';

const { assert, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { formatStatementEditSummary } = require( '../helpers/formatEditSummaries' );
const {
	newReplacePropertyStatementRequestBuilder,
	newReplaceStatementRequestBuilder,
	newGetPropertyStatementsRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );

function assertValid400Response( response, responseBodyErrorCode ) {
	expect( response ).to.have.status( 400 );
	assert.header( response, 'Content-Language', 'en' );
	assert.strictEqual( response.body.code, responseBodyErrorCode );
}

function assertValid404Response( response, responseBodyErrorCode ) {
	expect( response ).to.have.status( 404 );
	assert.header( response, 'Content-Language', 'en' );
	assert.strictEqual( response.body.code, responseBodyErrorCode );
}

describe( 'PUT statement tests', () => {
	let testPropertyId;
	let testStatementId;
	let testStatementPropertyId;
	let originalLastModified;
	let originalRevisionId;

	before( async () => {
		testStatementPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
		const createPropertyResponse = await entityHelper.createPropertyWithStatements( [
			entityHelper.newLegacyStatementWithRandomStringValue( testStatementPropertyId )
		] );
		testPropertyId = createPropertyResponse.entity.id;
		testStatementId = createPropertyResponse.entity.claims[ testStatementPropertyId ][ 0 ].id;

		const testPropertyCreationMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
		originalLastModified = new Date( testPropertyCreationMetadata.timestamp );
		originalRevisionId = testPropertyCreationMetadata.revid;

		// wait 1s before next test to ensure the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	[
		newReplacePropertyStatementRequestBuilder,
		( propertyId, statementId, statement ) => newReplaceStatementRequestBuilder( statementId, statement )
	].forEach( ( newReplaceRequestBuilder ) => {
		describe( newReplaceRequestBuilder().getRouteDescription(), () => {

			function assertValid200Response( response ) {
				expect( response ).to.have.status( 200 );
				assert.strictEqual( response.body.id, testStatementId );
				assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
				assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
				assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
			}

			describe( '200 success response ', () => {

				it( 'can replace a statement to an property with edit metadata omitted', async () => {
					const statementSerialization = entityHelper.newStatementWithRandomStringValue(
						testStatementPropertyId
					);
					const response = await newReplaceRequestBuilder(
						testPropertyId,
						testStatementId,
						statementSerialization
					).assertValidRequest().makeRequest();

					assertValid200Response( response );

					assert.deepEqual(
						response.body.value.content,
						statementSerialization.value.content
					);
					const { comment } = await entityHelper.getLatestEditMetadata( testPropertyId );
					assert.strictEqual(
						comment,
						formatStatementEditSummary(
							'wbsetclaim',
							'update',
							statementSerialization.property.id,
							statementSerialization.value.content
						)
					);
				} );

				it( 'can replace a statement to an property with edit metadata provided', async () => {
					const user = await action.robby(); // robby is a bot
					const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
					const editSummary = 'omg look i made an edit';
					const statementSerialization = entityHelper.newStatementWithRandomStringValue(
						testStatementPropertyId
					);
					const response = await newReplaceRequestBuilder(
						testPropertyId,
						testStatementId,
						statementSerialization
					).withJsonBodyParam( 'tags', [ tag ] )
						.withJsonBodyParam( 'bot', true )
						.withJsonBodyParam( 'comment', editSummary )
						.withUser( user )
						.assertValidRequest().makeRequest();

					assertValid200Response( response );
					assert.deepEqual(
						response.body.value.content,
						statementSerialization.value.content
					);

					const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
					assert.deepEqual( editMetadata.tags, [ tag ] );
					assert.property( editMetadata, 'bot' );
					assert.strictEqual(
						editMetadata.comment,
						formatStatementEditSummary(
							'wbsetclaim',
							'update',
							statementSerialization.property.id,
							statementSerialization.value.content,
							editSummary
						)
					);
					assert.strictEqual( editMetadata.user, user.username );
				} );

				it( 'is idempotent: repeating the same request only results in one edit', async () => {
					const statementSerialization = entityHelper.newStatementWithRandomStringValue(
						testStatementPropertyId
					);
					const requestTemplate = newReplaceRequestBuilder(
						testPropertyId,
						testStatementId,
						statementSerialization
					).assertValidRequest();

					const response1 = await requestTemplate.makeRequest();
					const response2 = await requestTemplate.makeRequest();

					assertValid200Response( response1 );
					assertValid200Response( response2 );

					assert.strictEqual( response1.headers.etag, response2.headers.etag );
					assert.strictEqual( response1.headers[ 'last-modified' ], response2.headers[ 'last-modified' ] );
					assert.deepEqual( response1.body, response2.body );
				} );

				it( 'replaces the statement in place without changing the order', async () => {
					// This is tested here by creating a new test property with three statements, replacing the
					// middle one and then checking that it's still in the middle afterwards.
					const newTestProperty = ( await entityHelper.createPropertyWithStatements( [
						entityHelper.newLegacyStatementWithRandomStringValue( testStatementPropertyId ),
						entityHelper.newLegacyStatementWithRandomStringValue( testStatementPropertyId ),
						entityHelper.newLegacyStatementWithRandomStringValue( testStatementPropertyId )
					] ) ).entity;

					const originalSecondStatement = newTestProperty.claims[ testStatementPropertyId ][ 1 ];
					const newSecondStatement = entityHelper.newStatementWithRandomStringValue(
						testStatementPropertyId
					);

					await newReplaceRequestBuilder(
						newTestProperty.id,
						originalSecondStatement.id,
						newSecondStatement
					).makeRequest();

					const actualSecondStatement = ( await newGetPropertyStatementsRequestBuilder(
						newTestProperty.id
					).makeRequest() ).body[ testStatementPropertyId ][ 1 ];

					assert.strictEqual( actualSecondStatement.id, originalSecondStatement.id );
					assert.strictEqual(
						actualSecondStatement.value.content,
						newSecondStatement.value.content
					);
					assert.notEqual(
						actualSecondStatement.value.content,
						originalSecondStatement.mainsnak.datavalue.value
					);
				} );

			} );

			describe( '400 error response', () => {

				it( 'statement ID contains invalid entity ID', async () => {
					const statementId = testStatementId.replace( 'P', 'X' );
					const statementSerialization = entityHelper.newStatementWithRandomStringValue(
						testStatementPropertyId
					);
					const response = await newReplaceRequestBuilder(
						testPropertyId,
						statementId,
						statementSerialization
					).assertInvalidRequest().makeRequest();

					assertValid400Response( response, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );

				it( 'statement ID is invalid format', async () => {
					const statementId = 'not-a-valid-format';
					const statementSerialization = entityHelper.newStatementWithRandomStringValue(
						testStatementPropertyId
					);
					const response = await newReplaceRequestBuilder(
						testPropertyId,
						statementId,
						statementSerialization
					).assertInvalidRequest().makeRequest();

					assertValid400Response( response, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );

				it( 'comment too long', async () => {
					const comment = 'x'.repeat( 501 );
					const statementSerialization = entityHelper.newStatementWithRandomStringValue(
						testStatementPropertyId
					);
					const response = await newReplaceRequestBuilder(
						testPropertyId,
						testStatementId,
						statementSerialization
					).withJsonBodyParam( 'comment', comment )
						.assertValidRequest().makeRequest();

					assertValid400Response( response, 'comment-too-long' );
					assert.include( response.body.message, '500' );
				} );

				it( 'invalid operation - new statement has a different Statement ID', async () => {
					const newStatementData = entityHelper.newStatementWithRandomStringValue(
						testStatementPropertyId
					);
					newStatementData.id = testPropertyId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newReplaceRequestBuilder(
						testPropertyId,
						testStatementId,
						newStatementData
					).assertInvalidRequest().makeRequest();

					assertValid400Response( response, 'invalid-operation-change-statement-id' );
				} );

				it( 'invalid operation - new statement has a different Property ID', async () => {
					const differentPropertyId = ( await entityHelper.createEntity(
						'property',
						{ datatype: 'string' }
					) ).entity.id;
					const response = await newReplaceRequestBuilder(
						testPropertyId,
						testStatementId,
						entityHelper.newStatementWithRandomStringValue( differentPropertyId )
					).assertValidRequest().makeRequest();

					assertValid400Response( response, 'invalid-operation-change-property-of-statement' );
				} );

				it( 'invalid edit tag', async () => {
					const invalidEditTag = 'invalid tag';
					const statementSerialization = entityHelper.newStatementWithRandomStringValue(
						testStatementPropertyId
					);
					const response = await newReplaceRequestBuilder(
						testPropertyId,
						testStatementId,
						statementSerialization
					).withJsonBodyParam( 'tags', [ invalidEditTag ] )
						.assertValidRequest().makeRequest();

					assertValid400Response( response, 'invalid-edit-tag' );
					assert.include( response.body.message, invalidEditTag );
				} );

				it( 'invalid edit tag type', async () => {
					const statementSerialization = entityHelper.newStatementWithRandomStringValue(
						testStatementPropertyId
					);
					const response = await newReplaceRequestBuilder(
						testPropertyId,
						testStatementId,
						statementSerialization
					).withJsonBodyParam( 'tags', 'not an array' )
						.assertInvalidRequest().makeRequest();

					expect( response ).to.have.status( 400 );
					assert.strictEqual( response.body.code, 'invalid-request-body' );
					assert.strictEqual( response.body.fieldName, 'tags' );
					assert.strictEqual( response.body.expectedType, 'array' );
				} );

				it( 'invalid bot flag type', async () => {
					const statementSerialization = entityHelper.newStatementWithRandomStringValue(
						testStatementPropertyId
					);
					const response = await newReplaceRequestBuilder(
						testPropertyId,
						testStatementId,
						statementSerialization
					).withJsonBodyParam( 'bot', 'should be a boolean' )
						.assertInvalidRequest().makeRequest();

					expect( response ).to.have.status( 400 );
					assert.strictEqual( response.body.code, 'invalid-request-body' );
					assert.strictEqual( response.body.fieldName, 'bot' );
					assert.strictEqual( response.body.expectedType, 'boolean' );
				} );

				it( 'invalid comment type', async () => {
					const statementSerialization = entityHelper.newStatementWithRandomStringValue(
						testStatementPropertyId
					);
					const response = await newReplaceRequestBuilder(
						testPropertyId,
						testStatementId,
						statementSerialization
					).withJsonBodyParam( 'comment', 1234 )
						.assertInvalidRequest().makeRequest();

					expect( response ).to.have.status( 400 );
					assert.strictEqual( response.body.code, 'invalid-request-body' );
					assert.strictEqual( response.body.fieldName, 'comment' );
					assert.strictEqual( response.body.expectedType, 'string' );
				} );

				it( 'invalid statement type', async () => {
					const response = await newReplaceRequestBuilder( testPropertyId, testStatementId, 'invalid' )
						.assertInvalidRequest().makeRequest();

					expect( response ).to.have.status( 400 );
					assert.strictEqual( response.body.code, 'invalid-request-body' );
					assert.strictEqual( response.body.fieldName, 'statement' );
					assert.strictEqual( response.body.expectedType, 'object' );
				} );

				it( 'invalid statement field', async () => {
					const statementSerialization = entityHelper.newStatementWithRandomStringValue(
						testStatementPropertyId
					);
					const invalidField = 'rank';
					const invalidValue = 'not-a-valid-rank';
					statementSerialization[ invalidField ] = invalidValue;

					const response = await newReplaceRequestBuilder(
						testPropertyId,
						testStatementId,
						statementSerialization
					).assertInvalidRequest().makeRequest();

					expect( response ).to.have.status( 400 );
					assert.strictEqual( response.body.code, 'statement-data-invalid-field' );
					assert.deepEqual( response.body.context, { path: invalidField, value: invalidValue } );
					assert.include( response.body.message, invalidField );
				} );

				it( 'missing statement field', async () => {
					const missingField = 'value';
					const statementSerialization = entityHelper.newStatementWithRandomStringValue(
						testStatementPropertyId
					);
					delete statementSerialization[ missingField ];

					const response = await newReplaceRequestBuilder(
						testPropertyId,
						testStatementId,
						statementSerialization
					).assertInvalidRequest().makeRequest();

					expect( response ).to.have.status( 400 );
					assert.strictEqual( response.body.code, 'statement-data-missing-field' );
					assert.deepEqual( response.body.context, { path: missingField } );
					assert.include( response.body.message, missingField );
				} );

			} );

			describe( '404 error response', () => {

				it( 'statement not found on property', async () => {
					const statementId = testPropertyId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newReplaceRequestBuilder(
						testPropertyId,
						statementId,
						entityHelper.newStatementWithRandomStringValue( testStatementPropertyId )
					).assertValidRequest().makeRequest();

					expect( response ).to.have.status( 404 );
					assert.header( response, 'Content-Language', 'en' );
					assert.equal( response.body.code, 'statement-not-found' );
					assert.include( response.body.message, statementId );
				} );

			} );
		} );

	} );

	describe( 'long route specific errors', () => {

		it( 'responds 400 for invalid property ID', async () => {
			const propertyId = 'X123';
			const statementSerialization = entityHelper.newStatementWithRandomStringValue(
				testStatementPropertyId
			);
			const response = await newReplacePropertyStatementRequestBuilder(
				propertyId,
				testStatementId,
				statementSerialization
			).assertInvalidRequest().makeRequest();

			assertValid400Response( response, 'invalid-property-id' );
			assert.include( response.body.message, propertyId );
		} );

		it( 'responds 404 property-not-found for nonexistent property', async () => {
			const propertyId = 'P9999999';
			const statementId = `${propertyId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newReplacePropertyStatementRequestBuilder( propertyId, statementId )
				.withJsonBodyParam( 'statement', entityHelper.newStatementWithRandomStringValue(
					testStatementPropertyId
				) )
				.assertValidRequest().makeRequest();

			assertValid404Response( response, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );

	} );

	describe( 'short route specific errors', () => {
		it( 'responds 400 invalid-statement-id if statement is not on a valid entity', async () => {
			const statementId = testStatementId.replace( 'P', 'X' );
			const statementSerialization = entityHelper.newStatementWithRandomStringValue(
				testStatementPropertyId
			);
			const response = await newReplaceStatementRequestBuilder( statementId, statementSerialization )
				.assertInvalidRequest().makeRequest();

			assertValid400Response( response, 'invalid-statement-id' );
			assert.include( response.body.message, statementId );
		} );

		it( 'responds 404 statement-not-found for nonexistent property', async () => {
			const statementId = 'P9999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newReplaceStatementRequestBuilder( statementId )
				.withJsonBodyParam( 'statement', entityHelper.newStatementWithRandomStringValue(
					testStatementPropertyId
				) )
				.assertValidRequest().makeRequest();

			assertValid404Response( response, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
	} );

} );
