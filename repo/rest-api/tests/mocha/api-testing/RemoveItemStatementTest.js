'use strict';

const { assert, action } = require( 'api-testing' );
const entityHelper = require( '../helpers/entityHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const formatStatementEditSummary = require( '../helpers/formatStatementEditSummary' );

async function addStatementWithRandomStringValue( itemId, propertyId ) {
	const response = await new RequestBuilder()
		.withRoute( 'POST', '/entities/items/{item_id}/statements' )
		.withPathParam( 'item_id', itemId )
		.withHeader( 'content-type', 'application/json' )
		.withJsonBodyParam(
			'statement',
			entityHelper.newStatementWithRandomStringValue( propertyId )
		).makeRequest();

	return response.body;
}

function newRemoveItemStatementRequestBuilder( itemId, statementId ) {
	return new RequestBuilder()
		.withRoute( 'DELETE', '/entities/items/{item_id}/statements/{statement_id}' )
		.withPathParam( 'item_id', itemId )
		.withPathParam( 'statement_id', statementId );
}

describe( 'DELETE /entities/items/{item_id}/statements/{statement_id}', () => {
	let testItemId;
	let testPropertyId;

	before( async () => {
		testItemId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
	} );

	function assertValid200Response( response ) {
		assert.equal( response.status, 200 );
		assert.equal( response.body, 'Statement deleted' );
	}

	async function verifyStatementDeleted( statementId ) {
		const verifyStatement = await new RequestBuilder()
			.withRoute( 'GET', '/statements/{statement_id}' )
			.withPathParam( 'statement_id', statementId )
			.makeRequest();

		assert.equal( verifyStatement.status, 404 );

	}

	describe( '200 success response', () => {
		let testStatement;

		beforeEach( async () => {
			testStatement = await addStatementWithRandomStringValue( testItemId, testPropertyId );
		} );

		it( "can remove an item's statement without request body", async () => {
			const response =
				await newRemoveItemStatementRequestBuilder( testItemId, testStatement.id )
					.assertValidRequest()
					.makeRequest();

			assertValid200Response( response );
			const { comment } = await entityHelper.getLatestEditMetadata( testItemId );
			assert.strictEqual(
				comment,
				formatStatementEditSummary( 'wbremoveclaims', 'remove', testStatement.mainsnak )
			);
			await verifyStatementDeleted( testStatement.id );
		} );

		it( "can remove an item's statement with edit metadata provided", async () => {
			const user = await action.mindy();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const editSummary = 'omg look i removed a statement';
			const response =
				await newRemoveItemStatementRequestBuilder( testItemId, testStatement.id )
					.withJsonBodyParam( 'tags', [ tag ] )
					.withJsonBodyParam( 'bot', true )
					.withJsonBodyParam( 'comment', editSummary )
					.withUser( user )
					.assertValidRequest()
					.makeRequest();

			assertValid200Response( response );
			await verifyStatementDeleted( testStatement.id );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatStatementEditSummary( 'wbremoveclaims',
					'remove',
					testStatement.mainsnak,
					editSummary
				)
			);
			assert.strictEqual( editMetadata.user, user.username );
		} );
	} );

	describe( '400 error response', () => {
		it( 'invalid item ID', async () => {
			const itemId = 'X123';
			const statementId = 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemoveItemStatementRequestBuilder( itemId, statementId )
				.assertInvalidRequest()
				.makeRequest();

			assert.equal( response.status, 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'invalid-item-id' );
			assert.include( response.body.message, itemId );
		} );

		it( 'statement ID contains invalid entity ID', async () => {
			const statementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemoveItemStatementRequestBuilder( testItemId, statementId )
				.assertInvalidRequest()
				.makeRequest();

			assert.equal( response.status, 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'invalid-statement-id' );
			assert.include( response.body.message, statementId );
		} );

		it( 'statement ID is invalid format', async () => {
			const statementId = 'not-a-valid-format';
			const response = await newRemoveItemStatementRequestBuilder( testItemId, statementId )
				.assertInvalidRequest()
				.makeRequest();

			assert.equal( response.status, 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'invalid-statement-id' );
			assert.include( response.body.message, statementId );
		} );

		it( 'statement is not on an item', async () => {
			const statementId = 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemoveItemStatementRequestBuilder( testItemId, statementId )
				.assertValidRequest()
				.makeRequest();

			assert.equal( response.status, 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'invalid-statement-id' );
			assert.include( response.body.message, statementId );
		} );
	} );

	describe( '404 error response', () => {
		it( 'requested item does not exist', async () => {
			const itemId = 'Q999999';
			const statementId = `${itemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newRemoveItemStatementRequestBuilder( itemId, statementId )
				.assertValidRequest()
				.makeRequest();

			assert.equal( response.status, 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );
		it( 'requested item exists, but statement does not exist on item', async () => {
			const statementId = testItemId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response =
				await newRemoveItemStatementRequestBuilder( testItemId, statementId )
					.assertValidRequest()
					.makeRequest();

			assert.equal( response.status, 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
		it( "requested item exists, but statement's Item ID does not match", async () => {
			const statementId = 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response =
				await newRemoveItemStatementRequestBuilder( testItemId, statementId )
					.assertValidRequest()
					.makeRequest();

			assert.equal( response.status, 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
	} );

	describe( '415 error response', () => {
		it( 'unsupported media type', async () => {
			const contentType = 'multipart/form-data';
			const response = await newRemoveItemStatementRequestBuilder( testItemId, 'id-does-not-matter' )
				.withHeader( 'content-type', contentType )
				.makeRequest();

			assert.strictEqual( response.status, 415 );
			assert.strictEqual( response.body.message, `Unsupported Content-Type: '${contentType}'` );
		} );
	} );
} );
