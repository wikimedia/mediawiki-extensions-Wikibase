'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newGetItemStatementRequestBuilder,
	newGetStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );

describe( 'GET statement', () => {
	let testItemId;

	let testStatement;
	let testStatementWithDeletedProperty;

	let testLastModified;
	let testRevisionId;

	function assertValid200Response( response, statement ) {
		expect( response ).to.have.status( 200 );
		assert.equal( response.body.id, statement.id );
		assert.equal( response.header[ 'last-modified' ], testLastModified );
		assert.equal( response.header.etag, makeEtag( testRevisionId ) );
	}

	function assertValidErrorResponse( response, statusCode, responseBodyErrorCode, context = null ) {
		expect( response ).to.have.status( statusCode );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, responseBodyErrorCode );
		if ( context === null ) {
			assert.notProperty( response.body, 'context' );
		} else {
			assert.deepStrictEqual( response.body.context, context );
		}
	}

	before( async () => {
		const testPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
		const testPropertyIdToDelete = ( await entityHelper.createUniqueStringProperty() ).entity.id;

		const createItemResponse = await entityHelper.createItemWithStatements( [
			entityHelper.newLegacyStatementWithRandomStringValue( testPropertyId ),
			entityHelper.newLegacyStatementWithRandomStringValue( testPropertyIdToDelete )
		] );

		testItemId = createItemResponse.entity.id;
		testStatement = createItemResponse.entity.claims[ testPropertyId ][ 0 ];

		testStatementWithDeletedProperty = createItemResponse.entity.claims[ testPropertyIdToDelete ][ 0 ];
		await entityHelper.deleteProperty( testPropertyIdToDelete );

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

				assertValid200Response( response, testStatement );
			} );

			it( 'can get a statement with a deleted property', async () => {
				const response = await newGetStatementRequestBuilder( testStatementWithDeletedProperty.id )
					.assertValidRequest()
					.makeRequest();

				assertValid200Response( response, testStatementWithDeletedProperty );
				assert.equal( response.body.property[ 'data-type' ], null );
			} );

			describe( '400 error response', () => {
				it( 'statement ID contains invalid subject ID', async () => {
					const statementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newRequestBuilder( statementId )
						.assertInvalidRequest()
						.makeRequest();

					assertValidErrorResponse( response, 400, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );

				it( 'statement ID is invalid format', async () => {
					const statementId = 'not-a-valid-format';
					const response = await newRequestBuilder( statementId )
						.assertInvalidRequest()
						.makeRequest();

					assertValidErrorResponse( response, 400, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );
			} );

			describe( '404 error response', () => {
				it( 'statement not found on item', async () => {
					const statementId = testItemId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newRequestBuilder( statementId )
						.assertValidRequest()
						.makeRequest();

					assertValidErrorResponse( response, 404, 'statement-not-found' );
					assert.include( response.body.message, statementId );
				} );

				it( 'statement subject is a redirect', async () => {
					const redirectSource = await entityHelper.createRedirectForItem( testItemId );
					const statementId = redirectSource + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newRequestBuilder( statementId )
						.assertValidRequest()
						.makeRequest();

					assertValidErrorResponse( response, 404, 'statement-not-found' );
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

			assertValidErrorResponse( response, 400, 'invalid-item-id' );
			assert.include( response.body.message, itemId );
		} );

		it( 'responds 400 if subject id in endpoint is not an item id', async () => {
			const subjectId = 'P123';
			const statementId = 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetItemStatementRequestBuilder( subjectId, statementId )
				.assertInvalidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 400, 'invalid-item-id' );
			assert.include( response.body.message, subjectId );
		} );

		it( 'responds item-not-found if item does not exist', async () => {
			const itemId = 'Q999999';
			const response = await newGetItemStatementRequestBuilder( itemId, testStatement.id )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 404, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );

		it( 'responds item-not-found if item and statement do not exist but statement prefix does', async () => {
			const itemId = 'Q999999';
			const statementId = `${itemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newGetItemStatementRequestBuilder( itemId, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 404, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );

		it( 'responds item-not-found if item, statement, or statement prefix do not exist', async () => {
			const itemId = 'Q999999';
			const statementId = 'Q999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetItemStatementRequestBuilder( itemId, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 404, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );

		it( 'responds statement-not-found if item exists but statement prefix does not', async () => {
			const requestedItemId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
			const statementId = 'Q999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetItemStatementRequestBuilder( requestedItemId, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 404, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );

		it( 'responds statement-not-found if item and subject prefix exist but statement does not', async () => {
			const requestedItemId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
			const statementId = `${requestedItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newGetItemStatementRequestBuilder( requestedItemId, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 404, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );

		it( 'responds statement-not-found if item and statement exist, but do not match', async () => {
			const requestedItemId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
			const response = await newGetItemStatementRequestBuilder(
				requestedItemId,
				testStatement.id
			).assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 404, 'statement-not-found' );
			assert.include( response.body.message, testStatement.id );
		} );

		it( 'responds statement-not-found if statement id prefix is incorrect type', async () => {
			const requestedItemId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
			const statementId = 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetItemStatementRequestBuilder( requestedItemId, statementId )
				.assertInvalidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 404, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
	} );

	describe( 'short route specific errors', () => {
		it( 'responds statement-not-found if item not found', async () => {
			const statementId = 'Q999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetStatementRequestBuilder( statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 404, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
	} );

} );
