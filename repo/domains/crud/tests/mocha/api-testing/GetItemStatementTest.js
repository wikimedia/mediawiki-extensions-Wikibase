'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newGetItemStatementRequestBuilder,
	newGetStatementRequestBuilder,
	newAddItemStatementRequestBuilder,
	newCreateItemRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { runAllJobs } = require( 'api-testing/lib/wiki' );

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

	before( async () => {
		testItemId = await entityHelper.getItemId();
		// creating the statement with the to be deleted property first, so that creating the "happy path" property
		// hopefully invalidates any caches that could claim that this property still exists (T369702)
		const testStatementPropertyIdToDelete = ( await entityHelper.createUniqueStringProperty() ).body.id;
		testStatementWithDeletedProperty = ( await newAddItemStatementRequestBuilder(
			testItemId,
			entityHelper.newStatementWithRandomStringValue( testStatementPropertyIdToDelete )
		).makeRequest() ).body;
		await entityHelper.deleteProperty( testStatementPropertyIdToDelete );
		await runAllJobs(); // wait for secondary data to catch up after deletion

		const testStatementPropertyId = ( await entityHelper.createUniqueStringProperty() ).body.id;
		testStatement = ( await newAddItemStatementRequestBuilder(
			testItemId,
			entityHelper.newStatementWithRandomStringValue( testStatementPropertyId )
		).makeRequest() ).body;

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
					// Disabling the cache here so that it doesn't claim that the property still exists
					.withConfigOverride( 'wgMainCacheType', 0 )
					.assertValidRequest()
					.makeRequest();

				assertValid200Response( response, testStatementWithDeletedProperty );
				assert.equal( response.body.property.data_type, null );
			} );

			describe( '400 error response', () => {
				it( 'statement ID contains invalid subject ID', async () => {
					const response = await newRequestBuilder( 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
						.assertInvalidRequest()
						.makeRequest();

					assertValidError(
						response,
						400,
						'invalid-path-parameter',
						{ parameter: 'statement_id' }
					);
				} );

				it( 'statement ID is invalid format', async () => {
					const response = await newRequestBuilder( 'not-a-valid-format' )
						.assertInvalidRequest()
						.makeRequest();

					assertValidError(
						response,
						400,
						'invalid-path-parameter',
						{ parameter: 'statement_id' }
					);
				} );
			} );

			describe( '404 error response', () => {
				it( 'statement not found on item', async () => {
					const statementId = testItemId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newRequestBuilder( statementId )
						.assertValidRequest()
						.makeRequest();

					assertValidError( response, 404, 'resource-not-found', { resource_type: 'statement' } );
					assert.strictEqual( response.body.message, 'The requested resource does not exist' );
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

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'item_id' }
			);
		} );

		it( 'responds 400 if subject id in endpoint is not an item id', async () => {
			const subjectId = 'P123';
			const statementId = 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetItemStatementRequestBuilder( subjectId, statementId )
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
			const response = await newGetItemStatementRequestBuilder(
				requestedItemId,
				testStatement.id
			).assertValidRequest().makeRequest();

			const context = { item_id: requestedItemId, statement_id: testStatement.id };
			assertValidError( response, 400, 'item-statement-id-mismatch', context );
		} );

		it( 'responds 404 if statement subject is a redirect', async () => {
			const redirectSource = await entityHelper.createRedirectForItem( testItemId );
			const statementId = redirectSource + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetItemStatementRequestBuilder( redirectSource, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'statement' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );

		it( 'responds item-not-found if item, statement, or statement prefix do not exist', async () => {
			const itemId = 'Q999999';
			const statementId = 'Q999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetItemStatementRequestBuilder( itemId, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'item' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );

		it( 'responds statement not found if item and subject prefix exist but statement does not', async () => {
			const requestedItemId = await entityHelper.getItemId();
			const statementId = `${requestedItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newGetItemStatementRequestBuilder( requestedItemId, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'statement' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );
	} );

	describe( 'short route specific errors', () => {
		it( 'responds statement not found if item not found', async () => {
			const statementId = 'Q999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetStatementRequestBuilder( statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'statement' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );

		it( 'responds statement not found if statement subject is a redirect', async () => {
			const redirectSource = await entityHelper.createRedirectForItem( testItemId );
			const statementId = redirectSource + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetStatementRequestBuilder( statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'statement' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );
	} );

} );
