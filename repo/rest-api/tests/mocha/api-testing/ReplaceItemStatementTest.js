'use strict';

const { assert, action } = require( 'api-testing' );
const entityHelper = require( '../helpers/entityHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );

function newReplaceItemStatementRequestBuilder( itemId, statementId, statement ) {
	return new RequestBuilder()
		.withRoute( 'PUT', '/entities/items/{item_id}/statements/{statement_id}' )
		.withPathParam( 'item_id', itemId )
		.withPathParam( 'statement_id', statementId )
		.withJsonBodyParam( 'statement', statement );
}

function makeEtag( ...revisionIds ) {
	return revisionIds.map( ( revId ) => `"${revId}"` ).join( ',' );
}

describe( 'PUT /entities/items/{item_id}/statements/{statement_id}', () => {
	let testItemId;
	let testStatementId;
	let testPropertyId;
	let originalLastModified;
	let originalRevisionId;

	function assertValid200Response( response ) {
		assert.strictEqual( response.status, 200 );
		assert.strictEqual( response.body.id, testStatementId );
		assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
	}

	before( async () => {
		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
		const createEntityResponse = await entityHelper.createEntity( 'item', {
			claims: [ entityHelper.newStatementWithRandomStringValue( testPropertyId ) ]
		} );
		testItemId = createEntityResponse.entity.id;
		testStatementId = createEntityResponse.entity.claims[ testPropertyId ][ 0 ].id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		// wait 1s before modifications to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '200 success response ', () => {
		it( 'can replace a statement to an item with edit metadata omitted', async () => {
			const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
			const response = await newReplaceItemStatementRequestBuilder(
				testItemId,
				testStatementId,
				statementSerialization
			).assertValidRequest().makeRequest();

			assertValid200Response( response );

			assert.deepEqual(
				response.body.mainsnak.datavalue,
				statementSerialization.mainsnak.datavalue
			);
			const { comment } = await entityHelper.getLatestEditMetadata( testItemId );
			assert.empty( comment );
		} );

		it( 'can replace a statement to an item with edit metadata provided', async () => {
			const user = await action.mindy();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const editSummary = 'omg look, I made an edit!';
			const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
			const response = await newReplaceItemStatementRequestBuilder(
				testItemId,
				testStatementId,
				statementSerialization
			).withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', editSummary )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response );
			assert.deepEqual(
				response.body.mainsnak.datavalue,
				statementSerialization.mainsnak.datavalue
			);

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.deepEqual( editMetadata.tags, [ tag ] );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual( editMetadata.comment, editSummary );
			assert.strictEqual( editMetadata.user, user.username );
		} );

		it( 'is idempotent: repeating the same request only results in one edit', async () => {
			const requestTemplate = newReplaceItemStatementRequestBuilder(
				testItemId,
				testStatementId,
				entityHelper.newStatementWithRandomStringValue( testPropertyId )
			).assertValidRequest();

			const response1 = await requestTemplate.makeRequest();
			const response2 = await requestTemplate.makeRequest();

			assertValid200Response( response1 );
			assertValid200Response( response2 );

			assert.strictEqual( response2.headers.etag, response1.headers.etag );
			assert.strictEqual( response2.headers[ 'last-modified' ], response1.headers[ 'last-modified' ] );
			assert.deepEqual( response2.body, response1.body );
		} );

		it( 'replaces the statement in place without changing the order', async () => {
			// This is tested here by creating an item with 3 statements, replacing the middle one
			// and then checking that it's still in the middle afterwards.
			const item = ( await entityHelper.createEntity( 'item', {
				claims: [
					entityHelper.newStatementWithRandomStringValue( testPropertyId ),
					entityHelper.newStatementWithRandomStringValue( testPropertyId ),
					entityHelper.newStatementWithRandomStringValue( testPropertyId )
				]
			} ) ).entity;
			const originalSecondStatement = item.claims[ testPropertyId ][ 1 ];
			const newSecondStatement = entityHelper.newStatementWithRandomStringValue( testPropertyId );

			await newReplaceItemStatementRequestBuilder(
				item.id,
				originalSecondStatement.id,
				newSecondStatement
			).makeRequest();

			const actualSecondStatement = ( await new RequestBuilder()
				.withRoute( 'GET', '/entities/items/{item_id}/statements' )
				.withPathParam( 'item_id', item.id )
				.makeRequest() ).body[ testPropertyId ][ 1 ];

			assert.strictEqual( actualSecondStatement.id, originalSecondStatement.id );
			assert.strictEqual(
				actualSecondStatement.mainsnak.datavalue.value,
				newSecondStatement.mainsnak.datavalue.value
			);
			assert.notEqual(
				actualSecondStatement.mainsnak.datavalue.value,
				originalSecondStatement.mainsnak.datavalue.value
			);
		} );

	} );

	describe( '400 error response', () => {
		function assertValid400Response( response, responseBodyErrorCode ) {
			assert.strictEqual( response.status, 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.strictEqual( response.body.code, responseBodyErrorCode );
		}

		it( 'invalid item ID', async () => {
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

		it( 'statement ID contains invalid entity ID', async () => {
			const statementId = testStatementId.replace( 'Q', 'X' );
			const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
			const response = await newReplaceItemStatementRequestBuilder(
				testItemId,
				statementId,
				statementSerialization
			).assertInvalidRequest().makeRequest();

			assertValid400Response( response, 'invalid-statement-id' );
			assert.include( response.body.message, statementId );
		} );

		it( 'statement ID is invalid format', async () => {
			const statementId = 'not-a-valid-format';
			const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
			const response = await newReplaceItemStatementRequestBuilder(
				testItemId,
				statementId,
				statementSerialization
			).assertInvalidRequest().makeRequest();

			assertValid400Response( response, 'invalid-statement-id' );
			assert.include( response.body.message, statementId );
		} );

		it( 'statement is not on an item', async () => {
			const statementId = testStatementId.replace( 'Q', 'P' );
			const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
			const response = await newReplaceItemStatementRequestBuilder(
				testItemId,
				statementId,
				statementSerialization
			).assertValidRequest().makeRequest();

			assertValid400Response( response, 'invalid-statement-id' );
			assert.include( response.body.message, statementId );
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
			const response = await newReplaceItemStatementRequestBuilder(
				testItemId,
				testStatementId,
				statementSerialization
			).withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

			assertValid400Response( response, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid statement data', async () => {
			const invalidStatement = {
				invalidKey: []
			};
			const response = await newReplaceItemStatementRequestBuilder(
				testItemId,
				testStatementId,
				invalidStatement
			).assertInvalidRequest().makeRequest();

			assertValid400Response( response, 'invalid-statement-data' );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
			const response = await newReplaceItemStatementRequestBuilder(
				testItemId,
				testStatementId,
				statementSerialization
			).withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

			assertValid400Response( response, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
			const response = await newReplaceItemStatementRequestBuilder(
				testItemId,
				testStatementId,
				statementSerialization
			).withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			assert.strictEqual( response.status, 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag type', async () => {
			const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
			const response = await newReplaceItemStatementRequestBuilder(
				testItemId,
				testStatementId,
				statementSerialization
			).withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			assert.strictEqual( response.status, 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'invalid comment type', async () => {
			const statementSerialization = entityHelper.newStatementWithRandomStringValue( testPropertyId );
			const response = await newReplaceItemStatementRequestBuilder(
				testItemId,
				testStatementId,
				statementSerialization
			).withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			assert.strictEqual( response.status, 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'comment' );
			assert.strictEqual( response.body.expectedType, 'string' );
		} );

		it( 'invalid statement type', async () => {
			const response = await newReplaceItemStatementRequestBuilder(
				testItemId,
				testStatementId,
				'invalid'
			).assertInvalidRequest().makeRequest();

			assert.strictEqual( response.status, 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'statement' );
			assert.strictEqual( response.body.expectedType, 'object' );
		} );
	} );

	describe( '404 error response', () => {
		it( 'statement not found on item', async () => {
			const statementId = testItemId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newReplaceItemStatementRequestBuilder( testItemId, statementId )
				.withJsonBodyParam( 'statement', entityHelper.newStatementWithRandomStringValue( testPropertyId ) )
				.assertValidRequest()
				.makeRequest();

			assert.equal( response.status, 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );

		it( 'item not found', async () => {
			const itemId = 'Q9999999';
			const statementId = `${itemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newReplaceItemStatementRequestBuilder( itemId, statementId )
				.withJsonBodyParam( 'statement', entityHelper.newStatementWithRandomStringValue( testPropertyId ) )
				.assertValidRequest()
				.makeRequest();

			assert.equal( response.status, 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );
	} );

	describe( '403 error response', () => {
		it( 'user cannot edit Item', async () => {
			const createEntityResponse = await entityHelper.createEntity( 'item', {
				claims: [ entityHelper.newStatementWithRandomStringValue( testPropertyId ) ]
			} );
			const protectedItemId = createEntityResponse.entity.id;
			const statementId = Object.values( createEntityResponse.entity.claims )[ 0 ][ 0 ].id;

			await entityHelper.protectItem( protectedItemId );

			const response = await newReplaceItemStatementRequestBuilder(
				protectedItemId,
				statementId,
				entityHelper.newStatementWithRandomStringValue( testPropertyId )
			)
				.assertValidRequest()
				.makeRequest();

			assert.strictEqual( response.status, 403 );
			assert.strictEqual( response.body.httpCode, 403 );
			assert.strictEqual( response.body.httpReason, 'Forbidden' );
			assert.strictEqual( response.body.error, 'rest-write-denied' );
		} );
	} );

	describe( '415 error response', () => {
		it( 'unsupported media type', async () => {
			const contentType = 'multipart/form-data';
			const response = await newReplaceItemStatementRequestBuilder(
				testItemId,
				entityHelper.newStatementWithRandomStringValue( testPropertyId )
			)
				.withHeader( 'content-type', contentType )
				.makeRequest();

			assert.strictEqual( response.status, 415 );
			assert.strictEqual( response.body.message, `Unsupported Content-Type: '${contentType}'` );
		} );
	} );
} );
