'use strict';

const { assert, action } = require( 'api-testing' );
const entityHelper = require( '../helpers/entityHelper' );
const { requireExtensions } = require( '../../../../../tests/api-testing/utils' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const { newStatementWithRandomStringValue } = require( '../helpers/entityHelper' );

function newRemoveStatementRequestBuilder( statementId ) {
	return new RequestBuilder()
		.withRoute( 'DELETE', '/statements/{statement_id}' )
		.withPathParam( 'statement_id', statementId );
}

describe( 'DELETE /statements/{statement_id}', () => {
	let testItemId;
	let testStatement;

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

	describe( '200 success response ', () => {
		beforeEach( async () => {
			const createSingleItemResponse = await entityHelper.createSingleItem();
			testItemId = createSingleItemResponse.entity.id;
			const claims = createSingleItemResponse.entity.claims;
			testStatement = Object.values( claims )[ 0 ][ 0 ];
		} );

		it( 'can remove a statement without request body', async () => {
			const response = await newRemoveStatementRequestBuilder( testStatement.id )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response );
			const { comment } = await entityHelper.getLatestEditMetadata( testItemId );
			assert.empty( comment );
			await verifyStatementDeleted( testStatement.id );
		} );

		it( 'can remove a statement with edit metadata provided', async () => {
			const user = await action.mindy();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const editSummary = 'omg look i removed a statement';
			const response = await newRemoveStatementRequestBuilder( testStatement.id )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', editSummary )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response );
			await verifyStatementDeleted( testStatement.id );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.deepEqual( editMetadata.tags, [ tag ] );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual( editMetadata.comment, editSummary );
			assert.strictEqual( editMetadata.user, user.username );
		} );
	} );

	describe( '400 error response', () => {
		it( 'statement ID contains invalid entity ID', async () => {
			const statementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemoveStatementRequestBuilder( statementId )
				.assertInvalidRequest()
				.makeRequest();

			assert.equal( response.status, 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'invalid-statement-id' );
			assert.include( response.body.message, statementId );
		} );

		it( 'statement ID is invalid format', async () => {
			const statementId = 'not-a-valid-format';
			const response = await newRemoveStatementRequestBuilder( statementId )
				.assertInvalidRequest()
				.makeRequest();

			assert.equal( response.status, 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'invalid-statement-id' );
			assert.include( response.body.message, statementId );
		} );

		it( 'statement is not on an item', async () => {
			const statementId = 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemoveStatementRequestBuilder( statementId )
				.assertValidRequest()
				.makeRequest();

			assert.equal( response.status, 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'invalid-statement-id' );
			assert.include( response.body.message, statementId );
		} );
	} );

	describe( '403 error response', () => {
		it( 'user cannot edit Item', async () => {
			const propertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			const createEntityResponse = await entityHelper.createEntity( 'item', {
				claims: [ newStatementWithRandomStringValue( propertyId ) ]
			} );
			const protectedItemId = createEntityResponse.entity.id;
			const statementId = Object.values( createEntityResponse.entity.claims )[ 0 ][ 0 ].id;

			await entityHelper.protectItem( protectedItemId );

			const response = await newRemoveStatementRequestBuilder( statementId )
				.assertValidRequest()
				.makeRequest();

			assert.strictEqual( response.status, 403 );
			assert.strictEqual( response.body.httpCode, 403 );
			assert.strictEqual( response.body.httpReason, 'Forbidden' );
			assert.strictEqual( response.body.error, 'rest-write-denied' );
		} );
	} );

	describe( '404 error response', () => {
		it( 'statement not found on item', async () => {
			const statementId = testItemId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemoveStatementRequestBuilder( statementId )
				.assertValidRequest()
				.makeRequest();

			assert.equal( response.status, 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
		it( 'item not found', async () => {
			const statementId = 'Q321$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemoveStatementRequestBuilder( statementId )
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
			const response = await newRemoveStatementRequestBuilder( 'id-does-not-matter' )
				.withHeader( 'content-type', contentType )
				.makeRequest();

			assert.strictEqual( response.status, 415 );
			assert.strictEqual( response.body.message, `Unsupported Content-Type: '${contentType}'` );
		} );
	} );

	describe( 'authentication', () => {

		beforeEach( async () => {
			const createSingleItemResponse = await entityHelper.createSingleItem();
			const claims = createSingleItemResponse.entity.claims;
			testStatement = Object.values( claims )[ 0 ][ 0 ];
		} );

		it( 'has an X-Authenticated-User header with the logged in user', async () => {
			const mindy = await action.mindy();

			const response = await newRemoveStatementRequestBuilder( testStatement.id )
				.withUser( mindy )
				.makeRequest();

			assertValid200Response( response );
			assert.header( response, 'X-Authenticated-User', mindy.username );
		} );

		describe.skip( 'OAuth', () => { // Skipping due to apache auth header issues. See T305709
			before( requireExtensions( [ 'OAuth' ] ) );

			it( 'responds with an error given an invalid bearer token', async () => {
				const response = newRemoveStatementRequestBuilder( testStatement.id )
					.withHeader( 'Authorization', 'Bearer this-is-an-invalid-token' )
					.makeRequest();

				assert.equal( response.status, 403 );
			} );
		} );
	} );

} );
