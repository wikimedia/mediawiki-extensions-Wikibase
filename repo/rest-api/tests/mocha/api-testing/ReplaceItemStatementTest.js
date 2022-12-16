'use strict';

const { assert, action } = require( 'api-testing' );
const entityHelper = require( '../helpers/entityHelper' );
const formatStatementEditSummary = require( '../helpers/formatStatementEditSummary' );
const {
	newReplaceItemStatementRequestBuilder,
	newReplaceStatementRequestBuilder,
	newGetItemStatementsRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

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

describe( 'PUT statement tests', () => {
	let testItemId;
	let testStatementId;
	let testPropertyId;
	let originalLastModified;
	let originalRevisionId;

	before( async () => {
		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
		const createItemResponse = await entityHelper.createItemWithStatements( [
			entityHelper.newLegacyStatementWithRandomStringValue( testPropertyId )
		] );
		testItemId = createItemResponse.entity.id;
		testStatementId = createItemResponse.entity.claims[ testPropertyId ][ 0 ].id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		// wait 1s before modifications to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	[
		newReplaceItemStatementRequestBuilder,
		( itemId, statementId, statement ) => newReplaceStatementRequestBuilder( statementId, statement )
	].forEach( ( newReplaceRequestBuilder ) => {
		describe( newReplaceRequestBuilder().getRouteDescription(), () => {

			function assertValid200Response( response ) {
				assert.strictEqual( response.status, 200 );
				assert.strictEqual( response.body.id, testStatementId );
				assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
				assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
				assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
			}

			describe( '200 success response ', () => {

				it( 'can replace a statement to an item with edit metadata omitted', async () => {
					const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
					const response = await newReplaceRequestBuilder(
						testItemId,
						testStatementId,
						statementSerialization
					).assertValidRequest().makeRequest();

					assertValid200Response( response );

					assert.deepEqual(
						response.body.value.content,
						statementSerialization.value.content
					);
					const { comment } = await entityHelper.getLatestEditMetadata( testItemId );
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

				it( 'can replace a statement to an item with edit metadata provided', async () => {
					const user = await action.robby(); // robby is a bot
					const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
					const editSummary = 'omg look i made an edit';
					const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
					const response = await newReplaceRequestBuilder(
						testItemId,
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

					const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
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
					const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
					const requestTemplate = newReplaceRequestBuilder(
						testItemId,
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
					// This is tested here by creating a new test item with three statements, replacing the
					// middle one and then checking that it's still in the middle afterwards.
					const newTestItem = ( await entityHelper.createItemWithStatements( [
						entityHelper.newLegacyStatementWithRandomStringValue( testPropertyId ),
						entityHelper.newLegacyStatementWithRandomStringValue( testPropertyId ),
						entityHelper.newLegacyStatementWithRandomStringValue( testPropertyId )
					] ) ).entity;

					const originalSecondStatement = newTestItem.claims[ testPropertyId ][ 1 ];
					const newSecondStatement = entityHelper.newStatementWithRandomStringValue( testPropertyId );

					await newReplaceRequestBuilder(
						newTestItem.id,
						originalSecondStatement.id,
						newSecondStatement
					).makeRequest();

					const actualSecondStatement = ( await newGetItemStatementsRequestBuilder(
						newTestItem.id
					).makeRequest() ).body[ testPropertyId ][ 1 ];

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
					const statementId = testStatementId.replace( 'Q', 'X' );
					const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
					const response = await newReplaceRequestBuilder( testItemId, statementId, statementSerialization )
						.assertInvalidRequest().makeRequest();

					assertValid400Response( response, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );

				it( 'statement ID is invalid format', async () => {
					const statementId = 'not-a-valid-format';
					const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
					const response = await newReplaceRequestBuilder( testItemId, statementId, statementSerialization )
						.assertInvalidRequest().makeRequest();

					assertValid400Response( response, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );

				it( 'statement is not on an item', async () => {
					const statementId = testStatementId.replace( 'Q', 'P' );
					const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
					const response = await newReplaceRequestBuilder( testItemId, statementId, statementSerialization )
						.assertValidRequest().makeRequest();

					assertValid400Response( response, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );

				it( 'comment too long', async () => {
					const comment = 'x'.repeat( 501 );
					const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
					const response = await newReplaceRequestBuilder(
						testItemId,
						testStatementId,
						statementSerialization
					).withJsonBodyParam( 'comment', comment )
						.assertValidRequest().makeRequest();

					assertValid400Response( response, 'comment-too-long' );
					assert.include( response.body.message, '500' );
				} );

				it( 'invalid operation - new statement has a different Statement ID', async () => {
					const newStatementData = entityHelper.newStatementWithRandomStringValue( testPropertyId );
					newStatementData.id = testItemId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newReplaceRequestBuilder(
						testItemId,
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
						testItemId,
						testStatementId,
						entityHelper.newStatementWithRandomStringValue( differentPropertyId )
					).assertValidRequest().makeRequest();

					assertValid400Response( response, 'invalid-operation-change-property-of-statement' );
				} );

				it( 'invalid edit tag', async () => {
					const invalidEditTag = 'invalid tag';
					const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
					const response = await newReplaceRequestBuilder(
						testItemId,
						testStatementId,
						statementSerialization
					).withJsonBodyParam( 'tags', [ invalidEditTag ] )
						.assertValidRequest().makeRequest();

					assertValid400Response( response, 'invalid-edit-tag' );
					assert.include( response.body.message, invalidEditTag );
				} );

				it( 'invalid edit tag type', async () => {
					const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
					const response = await newReplaceRequestBuilder(
						testItemId,
						testStatementId,
						statementSerialization
					).withJsonBodyParam( 'tags', 'not an array' )
						.assertInvalidRequest().makeRequest();

					assert.strictEqual( response.status, 400 );
					assert.strictEqual( response.body.code, 'invalid-request-body' );
					assert.strictEqual( response.body.fieldName, 'tags' );
					assert.strictEqual( response.body.expectedType, 'array' );
				} );

				it( 'invalid bot flag type', async () => {
					const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
					const response = await newReplaceRequestBuilder(
						testItemId,
						testStatementId,
						statementSerialization
					).withJsonBodyParam( 'bot', 'should be a boolean' )
						.assertInvalidRequest().makeRequest();

					assert.strictEqual( response.status, 400 );
					assert.strictEqual( response.body.code, 'invalid-request-body' );
					assert.strictEqual( response.body.fieldName, 'bot' );
					assert.strictEqual( response.body.expectedType, 'boolean' );
				} );

				it( 'invalid comment type', async () => {
					const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
					const response = await newReplaceRequestBuilder(
						testItemId,
						testStatementId,
						statementSerialization
					).withJsonBodyParam( 'comment', 1234 )
						.assertInvalidRequest().makeRequest();

					assert.strictEqual( response.status, 400 );
					assert.strictEqual( response.body.code, 'invalid-request-body' );
					assert.strictEqual( response.body.fieldName, 'comment' );
					assert.strictEqual( response.body.expectedType, 'string' );
				} );

				it( 'invalid statement type', async () => {
					const response = await newReplaceRequestBuilder( testItemId, testStatementId, 'invalid' )
						.assertInvalidRequest().makeRequest();

					assert.strictEqual( response.status, 400 );
					assert.strictEqual( response.body.code, 'invalid-request-body' );
					assert.strictEqual( response.body.fieldName, 'statement' );
					assert.strictEqual( response.body.expectedType, 'object' );
				} );

				it( 'invalid statement field', async () => {
					const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
					const invalidField = 'rank';
					const invalidValue = 'not-a-valid-rank';
					statementSerialization[ invalidField ] = invalidValue;

					const response = await newReplaceRequestBuilder(
						testItemId,
						testStatementId,
						statementSerialization
					).assertInvalidRequest().makeRequest();

					assert.strictEqual( response.status, 400 );
					assert.strictEqual( response.body.code, 'statement-data-invalid-field' );
					assert.deepEqual( response.body.context, { path: invalidField, value: invalidValue } );
					assert.include( response.body.message, invalidField );
				} );

				it( 'missing statement field', async () => {
					const missingField = 'value';
					const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
					delete statementSerialization[ missingField ];

					const response = await newReplaceRequestBuilder(
						testItemId,
						testStatementId,
						statementSerialization
					).assertInvalidRequest().makeRequest();

					assert.strictEqual( response.status, 400 );
					assert.strictEqual( response.body.code, 'statement-data-missing-field' );
					assert.deepEqual( response.body.context, { path: missingField } );
					assert.include( response.body.message, missingField );
				} );

			} );

			describe( '404 error response', () => {

				it( 'statement not found on item', async () => {
					const statementId = testItemId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newReplaceRequestBuilder( testItemId, statementId )
						.withJsonBodyParam(
							'statement',
							entityHelper.newStatementWithRandomStringValue( testPropertyId )
						).assertValidRequest().makeRequest();

					assert.equal( response.status, 404 );
					assert.header( response, 'Content-Language', 'en' );
					assert.equal( response.body.code, 'statement-not-found' );
					assert.include( response.body.message, statementId );
				} );

			} );

			describe( '415 error response', () => {

				it( 'unsupported media type', async () => {
					const contentType = 'multipart/form-data';
					const response = await newReplaceRequestBuilder(
						testItemId,
						testStatementId,
						entityHelper.newStatementWithRandomStringValue( testPropertyId )
					).withHeader( 'content-type', contentType ).makeRequest();

					assert.strictEqual( response.status, 415 );
					assert.strictEqual( response.body.message, `Unsupported Content-Type: '${contentType}'` );
				} );
			} );

		} );

	} );

	describe( 'long route specific errors', () => {

		it( 'responds 400 for invalid item ID', async () => {
			const itemId = 'X123';
			const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
			const response = await newReplaceItemStatementRequestBuilder(
				itemId,
				testStatementId,
				statementSerialization
			).assertInvalidRequest().makeRequest();

			assertValid400Response( response, 'invalid-item-id' );
			assert.include( response.body.message, itemId );
		} );

		it( 'responds 404 item-not-found for nonexistent item', async () => {
			const itemId = 'Q9999999';
			const statementId = `${itemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newReplaceItemStatementRequestBuilder( itemId, statementId )
				.withJsonBodyParam( 'statement', entityHelper.newStatementWithRandomStringValue( testPropertyId ) )
				.assertValidRequest().makeRequest();

			assertValid404Response( response, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );

	} );

	describe( 'short route specific errors', () => {
		it( 'responds 404 statement-not-found for nonexistent item', async () => {
			const statementId = 'Q9999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newReplaceStatementRequestBuilder( statementId )
				.withJsonBodyParam( 'statement', entityHelper.newStatementWithRandomStringValue( testPropertyId ) )
				.assertValidRequest().makeRequest();

			assertValid404Response( response, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
	} );

} );
