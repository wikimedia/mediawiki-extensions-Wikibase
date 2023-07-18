'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newGetPropertyStatementRequestBuilder,
	newGetStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );

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
		const propertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
		const propertyIdToDelete = ( await entityHelper.createUniqueStringProperty() ).entity.id;

		const createPropertyResponse = await entityHelper.createPropertyWithStatements( [
			entityHelper.newLegacyStatementWithRandomStringValue( propertyId ),
			entityHelper.newLegacyStatementWithRandomStringValue( propertyIdToDelete )
		] );

		testPropertyId = createPropertyResponse.entity.id;
		testStatement = createPropertyResponse.entity.claims[ propertyId ][ 0 ];

		testStatementWithDeletedProperty = createPropertyResponse.entity.claims[ propertyIdToDelete ][ 0 ];
		await entityHelper.deleteProperty( propertyIdToDelete );

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
				it( 'statement not found on property', async () => {
					const statementId = testPropertyId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
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
		it( 'responds 400 for invalid Property ID', async () => {
			const propertyId = 'X123';
			const statementId = 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetPropertyStatementRequestBuilder( propertyId, statementId )
				.assertInvalidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 400, 'invalid-property-id' );
			assert.include( response.body.message, propertyId );
		} );

		it( 'responds 400 if subject id in endpoint is not an property id', async () => {
			const subjectId = 'Q123';
			const statementId = 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetPropertyStatementRequestBuilder( subjectId, statementId )
				.assertInvalidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 400, 'invalid-property-id' );
			assert.include( response.body.message, subjectId );
		} );

		it( 'responds property-not-found if property does not exist', async () => {
			const propertyId = 'P999999';
			const response = await newGetPropertyStatementRequestBuilder( propertyId, testStatement.id )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 404, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );

		it( 'responds property-not-found if property and statement do not exist' +
			'but statement prefix does', async () => {
			const propertyId = 'P999999';
			const statementId = `${propertyId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newGetPropertyStatementRequestBuilder( propertyId, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 404, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );

		it( 'responds property-not-found if property, statement, or statement prefix do not exist', async () => {
			const propertyId = 'P999999';
			const statementId = 'P999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetPropertyStatementRequestBuilder( propertyId, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 404, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );

		it( 'responds statement-not-found if property exists but statement prefix does not', async () => {
			const requestedPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			const statementId = 'P999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetPropertyStatementRequestBuilder( requestedPropertyId, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 404, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );

		it( 'responds statement-not-found if property and subject prefix exist but statement does not', async () => {
			const requestedPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			const statementId = `${requestedPropertyId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newGetPropertyStatementRequestBuilder( requestedPropertyId, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 404, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );

		it( 'responds statement-not-found if property and statement exist, but do not match', async () => {
			const requestedPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			const response = await newGetPropertyStatementRequestBuilder(
				requestedPropertyId,
				testStatement.id
			).assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 404, 'statement-not-found' );
			assert.include( response.body.message, testStatement.id );
		} );

		it( 'responds statement-not-found if statement id prefix is incorrect type', async () => {
			const requestedPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			const statementId = 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetPropertyStatementRequestBuilder( requestedPropertyId, statementId )
				.assertInvalidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 404, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
	} );

	describe( 'short route specific errors', () => {
		it( 'responds statement-not-found if property not found', async () => {
			const statementId = 'P999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newGetStatementRequestBuilder( statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 404, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
	} );

} );
