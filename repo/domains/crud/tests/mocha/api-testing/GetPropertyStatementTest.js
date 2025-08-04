'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newGetPropertyStatementRequestBuilder,
	newGetStatementRequestBuilder,
	newAddPropertyStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { runAllJobs } = require( 'api-testing/lib/wiki' );

describe( 'GET statement', () => {
	let testPropertyId;

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
		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).body.id;

		// creating the statement with the to be deleted property first, so that creating the "happy path" property
		// hopefully invalidates any caches that could claim that this property still exists (T369702)
		const testStatementPropertyIdToDelete = ( await entityHelper.createUniqueStringProperty() ).body.id;
		testStatementWithDeletedProperty = ( await newAddPropertyStatementRequestBuilder(
			testPropertyId,
			entityHelper.newStatementWithRandomStringValue( testStatementPropertyIdToDelete )
		).makeRequest() ).body;
		await entityHelper.deleteProperty( testStatementPropertyIdToDelete );
		await runAllJobs(); // wait for secondary data to catch up after deletion

		const testStatementPropertyId = ( await entityHelper.createUniqueStringProperty() ).body.id;
		testStatement = ( await newAddPropertyStatementRequestBuilder(
			testPropertyId,
			entityHelper.newStatementWithRandomStringValue( testStatementPropertyId )
		).makeRequest() ).body;

		const testPropertyCreationMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
		testLastModified = testPropertyCreationMetadata.timestamp;
		testRevisionId = testPropertyCreationMetadata.revid;
	} );

	[
		( statementId ) => newGetPropertyStatementRequestBuilder( testPropertyId, statementId ),
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
					// Randomizing the cache key to guarantee a cache miss so that the cache doesn't claim that the
					// property still exists. Disabling it via wgMainCacheType doesn't work, and neither does setting
					// sharedCacheDuration to 0.
					.withConfigOverride( 'wgWBRepoSettings', { sharedCacheKeyGroup: utils.uniq() } )
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
				it( 'statement not found on property', async () => {
					const statementId = testPropertyId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
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
		it( 'responds 400 for invalid Property ID', async () => {
			const response = await newGetPropertyStatementRequestBuilder(
				'X123',
				'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE'
			).assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'property_id' }
			);
		} );

		it( 'responds 400 if subject id in endpoint is not a property id', async () => {
			const subjectId = 'Q123';
			const statementId = 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetPropertyStatementRequestBuilder( subjectId, statementId )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'property_id' }
			);
		} );

		it( 'responds property-not-found if property, statement, or statement prefix do not exist', async () => {
			const propertyId = 'P999999';
			const statementId = 'P999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetPropertyStatementRequestBuilder( propertyId, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'property' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );

		it( 'responds statement not found if property and subject prefix exist but statement does not', async () => {
			const requestedPropertyId = ( await entityHelper.createUniqueStringProperty() ).body.id;
			const statementId = `${requestedPropertyId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newGetPropertyStatementRequestBuilder( requestedPropertyId, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'statement' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );

		it( 'responds property-statement-id-mismatch if property and statement do not match', async () => {
			const requestedPropertyId = ( await entityHelper.createUniqueStringProperty() ).body.id;
			const response = await newGetPropertyStatementRequestBuilder( requestedPropertyId, testStatement.id )
				.assertValidRequest()
				.makeRequest();

			const context = { property_id: requestedPropertyId, statement_id: testStatement.id };
			assertValidError( response, 400, 'property-statement-id-mismatch', context );
		} );
	} );

	describe( 'short route specific errors', () => {
		it( 'responds statement not found if property not found', async () => {
			const statementId = 'P999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetStatementRequestBuilder( statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'statement' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );
	} );

} );
