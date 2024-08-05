'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newGetPropertyStatementRequestBuilder,
	newGetStatementRequestBuilder
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
		const statementPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
		const statementPropertyIdToDelete = ( await entityHelper.createUniqueStringProperty() ).entity.id;

		const createPropertyResponse = await entityHelper.createPropertyWithStatements( [
			entityHelper.newLegacyStatementWithRandomStringValue( statementPropertyId ),
			entityHelper.newLegacyStatementWithRandomStringValue( statementPropertyIdToDelete )
		] );

		testPropertyId = createPropertyResponse.entity.id;
		testStatement = createPropertyResponse.entity.claims[ statementPropertyId ][ 0 ];

		testStatementWithDeletedProperty = createPropertyResponse.entity.claims[ statementPropertyIdToDelete ][ 0 ];
		await entityHelper.deleteProperty( statementPropertyIdToDelete );
		await runAllJobs(); // wait for secondary data to catch up after deletion

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

			let retries = 0;
			it( 'can get a statement with a deleted property', async function () {
				// The property deletion isn't always immediately taking effect here for some reason.
				// Retrying with a timeout so that secondary data can catch up.
				this.retries( 3 );
				if ( retries > 0 ) {
					await new Promise( ( resolve ) => {
						setTimeout( resolve, 1000 );
					} );
				}
				retries++;

				const response = await newGetStatementRequestBuilder( testStatementWithDeletedProperty.id )
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

					assertValidError( response, 404, 'statement-not-found' );
					assert.include( response.body.message, statementId );
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

			assertValidError( response, 404, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );

		it( 'responds statement-not-found if property and subject prefix exist but statement does not', async () => {
			const requestedPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			const statementId = `${requestedPropertyId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newGetPropertyStatementRequestBuilder( requestedPropertyId, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );

		it( 'responds property-statement-id-mismatch if property and statement do not match', async () => {
			const requestedPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			const response = await newGetPropertyStatementRequestBuilder( requestedPropertyId, testStatement.id )
				.assertValidRequest()
				.makeRequest();

			const context = { property_id: requestedPropertyId, statement_id: testStatement.id };
			assertValidError( response, 400, 'property-statement-id-mismatch', context );
		} );
	} );

	describe( 'short route specific errors', () => {
		it( 'responds statement-not-found if property not found', async () => {
			const statementId = 'P999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetStatementRequestBuilder( statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
	} );

} );
