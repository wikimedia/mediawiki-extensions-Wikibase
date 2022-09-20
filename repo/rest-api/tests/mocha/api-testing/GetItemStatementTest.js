'use strict';

const { assert } = require( 'api-testing' );
const entityHelper = require( '../helpers/entityHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );

function newGetItemStatementRequestBuilder( itemId, statementId ) {
	return new RequestBuilder()
		.withRoute( 'GET', '/entities/items/{item_id}/statements/{statement_id}' )
		.withPathParam( 'item_id', itemId )
		.withPathParam( 'statement_id', statementId );
}

function newGetStatementRequestBuilder( statementId ) {
	return new RequestBuilder()
		.withRoute( 'GET', '/statements/{statement_id}' )
		.withPathParam( 'statement_id', statementId );
}

function makeEtag( ...revisionIds ) {
	return revisionIds.map( ( revId ) => `"${revId}"` ).join( ',' );
}

describe( 'GET statement', () => {
	let testItemId;
	let testStatement;
	let testLastModified;
	let testRevisionId;

	function assertValid200Response( response ) {
		assert.equal( response.status, 200 );
		assert.equal( response.body.id, testStatement.id );
		assert.equal( response.header[ 'last-modified' ], testLastModified );
		assert.equal( response.header.etag, makeEtag( testRevisionId ) );
	}

	before( async () => {
		const createSingleItemResponse = await entityHelper.createSingleItem();
		testItemId = createSingleItemResponse.entity.id;
		const claims = createSingleItemResponse.entity.claims;
		testStatement = Object.values( claims )[ 0 ][ 0 ];

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		testLastModified = testItemCreationMetadata.timestamp;
		testRevisionId = testItemCreationMetadata.revid;
	} );

	[
		( statementId ) => newGetItemStatementRequestBuilder( testItemId, statementId ),
		newGetStatementRequestBuilder
	].forEach( ( newRequestBuilder ) => {
		describe( newRequestBuilder().getRouteDescription(), () => {
			it( 'can GET a statement with metadata', async () => {
				const response = await newRequestBuilder( testStatement.id )
					.assertValidRequest()
					.makeRequest();

				assertValid200Response( response );
			} );

			describe( '400 error response', () => {

				it( 'statement ID contains invalid entity ID', async () => {
					const statementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newRequestBuilder( statementId )
						.assertInvalidRequest()
						.makeRequest();

					assert.equal( response.status, 400 );
					assert.header( response, 'Content-Language', 'en' );
					assert.equal( response.body.code, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );

				it( 'statement ID is invalid format', async () => {
					const statementId = 'not-a-valid-format';
					const response = await newRequestBuilder( statementId )
						.assertInvalidRequest()
						.makeRequest();

					assert.equal( response.status, 400 );
					assert.header( response, 'Content-Language', 'en' );
					assert.equal( response.body.code, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );

				it( 'statement is not on an item', async () => {
					const statementId = 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newRequestBuilder( statementId )
						.assertValidRequest()
						.makeRequest();

					assert.equal( response.status, 400 );
					assert.header( response, 'Content-Language', 'en' );
					assert.equal( response.body.code, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );
			} );

			describe( '404 error response', () => {
				it( 'statement not found on item', async () => {
					const statementId = testItemId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newRequestBuilder( statementId )
						.assertValidRequest()
						.makeRequest();

					assert.equal( response.status, 404 );
					assert.header( response, 'Content-Language', 'en' );
					assert.equal( response.body.code, 'statement-not-found' );
					assert.include( response.body.message, statementId );
				} );
			} );
		} );
	} );

	describe( 'long route specific errors', () => {
		it( 'responds 400 for invalid Item ID', async () => {
			const itemId = 'X123';
			const statementId = 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetItemStatementRequestBuilder( itemId, statementId )
				.assertInvalidRequest()
				.makeRequest();

			assert.equal( response.status, 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'invalid-item-id' );
			assert.include( response.body.message, itemId );
		} );

		it( 'responds 404 if requested item not found', async () => {
			const itemId = 'Q999999';
			const statementId = `${itemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newGetItemStatementRequestBuilder( itemId, statementId )
				.assertValidRequest()
				.makeRequest();

			assert.equal( response.status, 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );

		it( 'responds 404 requested Item ID and Statement ID mismatch', async () => {
			const requestedItemId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
			const response = await newGetItemStatementRequestBuilder(
				requestedItemId,
				testStatement.id
			).assertValidRequest().makeRequest();

			assert.equal( response.status, 404 );
			assert.equal( response.body.code, 'statement-not-found' );
			assert.include( response.body.message, testStatement.id );
		} );
	} );

	describe( 'short route specific errors', () => {
		it( 'responds 404 if item not found', async () => {
			const statementId = 'Q999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetStatementRequestBuilder( statementId )
				.assertValidRequest()
				.makeRequest();

			assert.equal( response.status, 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
	} );

} );
