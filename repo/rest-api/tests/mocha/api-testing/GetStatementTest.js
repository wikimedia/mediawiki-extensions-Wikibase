'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newGetItemStatementRequestBuilder,
	newGetStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );

function assertValid200Response( response, statementId, testData ) {
	expect( response ).to.have.status( 200 );
	assert.equal( response.body.id, statementId );
	assert.equal( response.header[ 'last-modified' ], testData.lastModified );
	assert.equal( response.header.etag, makeEtag( testData.revisionId ) );
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

describe( 'Retrieve Single Statement', () => {
	const allTestData = {};
	const requestSetBySubjectType = {
		item: {
			long: ( { subjectId, statementId } ) => newGetItemStatementRequestBuilder( subjectId, statementId ),
			short: ( { statementId } ) => newGetStatementRequestBuilder( statementId )
		},
		property: {
			short: ( { statementId } ) => newGetStatementRequestBuilder( statementId )
		}
	};

	before( async () => {
		for ( const subjectType of Object.keys( requestSetBySubjectType ) ) {
			const propertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			const propertyIdToDelete = ( await entityHelper.createUniqueStringProperty() ).entity.id;

			const createEntityResponse = await entityHelper.createEntityWithStatements( [
				entityHelper.newLegacyStatementWithRandomStringValue( propertyId ),
				entityHelper.newLegacyStatementWithRandomStringValue( propertyIdToDelete )
			], subjectType );

			const subjectId = createEntityResponse.entity.id;
			const otherSubjectId = ( await entityHelper.createEntityWithStatements( [], subjectType ) ).entity.id;
			const statementId = createEntityResponse.entity.claims[ propertyId ][ 0 ].id;

			const statementIdWithDeletedProperty = createEntityResponse.entity.claims[ propertyIdToDelete ][ 0 ].id;
			await entityHelper.deleteProperty( propertyIdToDelete );

			const entityCreationMetadata = await entityHelper.getLatestEditMetadata( subjectId );

			allTestData[ subjectType ] = {
				subjectId,
				otherSubjectId,
				statementId,
				statementIdWithDeletedProperty,
				lastModified: entityCreationMetadata.timestamp,
				revisionId: entityCreationMetadata.revid
			};
		}
	} );

	for ( const [ subjectType, requestBuilderByRequestType ] of Object.entries( requestSetBySubjectType ) ) {
		for ( const [ requestType, newRequestBuilder ] of Object.entries( requestBuilderByRequestType ) ) {
			let testData;

			describe( `${newRequestBuilder( {} ).getRouteDescription()} [${subjectType}]`, () => {
				before( () => {
					testData = allTestData[ subjectType ];
				} );

				it( 'can GET a statement with metadata', async () => {
					const response = await newRequestBuilder( testData )
						.assertValidRequest()
						.makeRequest();

					assertValid200Response( response, testData.statementId, testData );
				} );

				it( 'can get a statement with a deleted property', async () => {
					const requestInput = {
						subjectId: testData.subjectId,
						statementId: testData.statementIdWithDeletedProperty
					};
					const response = await newRequestBuilder( requestInput )
						.assertValidRequest()
						.makeRequest();

					assertValid200Response( response, testData.statementIdWithDeletedProperty, testData );
					assert.equal( response.body.property[ 'data-type' ], null );
				} );

				describe( '400 error response', () => {
					it( 'statement ID contains invalid subject ID', async () => {
						const statementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
						const response = await newRequestBuilder( { subjectId: testData.subjectId, statementId } )
							.assertInvalidRequest()
							.makeRequest();

						assertValidErrorResponse( response, 400, 'invalid-statement-id' );
						assert.include( response.body.message, statementId );
					} );

					it( 'statement ID is invalid format', async () => {
						const statementId = 'not-a-valid-format';
						const response = await newRequestBuilder( { subjectId: testData.subjectId, statementId } )
							.assertInvalidRequest()
							.makeRequest();

						assertValidErrorResponse( response, 400, 'invalid-statement-id' );
						assert.include( response.body.message, statementId );
					} );
				} );

				describe( '404 error response', () => {
					it( `statement not found on ${subjectType}`, async () => {
						const statementId = `${testData.subjectId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
						const response = await newRequestBuilder( { subjectId: testData.subjectId, statementId } )
							.assertValidRequest()
							.makeRequest();

						assertValidErrorResponse( response, 404, 'statement-not-found' );
						assert.include( response.body.message, statementId );
					} );

					it( 'responds statement-not-found if statement id prefix is incorrect type', async () => {
						const incorrectSubjectType = subjectType === 'item' ? 'property' : 'item';
						const incorrectSubjectId = allTestData[ incorrectSubjectType ].subjectId;
						const statementId = `${incorrectSubjectId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
						const response = await newRequestBuilder( { subjectId: testData.subjectId, statementId } )
							.assertValidRequest()
							.makeRequest();

						assertValidErrorResponse( response, 404, 'statement-not-found' );
						assert.include( response.body.message, statementId );
					} );

					if ( subjectType === 'item' ) {
						it( 'statement subject is a redirect', async () => {
							const redirectSource = await entityHelper.createRedirectForItem( testData.subjectId );
							const statementId = `${redirectSource}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
							const response = await newRequestBuilder( { subjectId: testData.subjectId, statementId } )
								.assertValidRequest()
								.makeRequest();

							assertValidErrorResponse( response, 404, 'statement-not-found' );
							assert.include( response.body.message, statementId );
						} );
					}
				} );

				if ( requestType === 'long' ) {
					describe( 'long route specific tests', () => {
						it( `responds 400 for invalid ${subjectType} ID`, async () => {
							const subjectId = 'X123';
							const statementId = testData.statementId;
							const response = await newRequestBuilder( { subjectId, statementId } )
								.assertInvalidRequest()
								.makeRequest();

							assertValidErrorResponse( response, 400, `invalid-${subjectType}-id` );
							assert.include( response.body.message, subjectId );
						} );

						it( "responds 400 if subject id doesn't match endpoint", async () => {
							const subjectId = subjectType !== 'property' ? 'P123' : 'Q123';
							const statementId = testData.statementId;
							const response = await newRequestBuilder( { subjectId, statementId } )
								.assertInvalidRequest()
								.makeRequest();

							assertValidErrorResponse( response, 400, `invalid-${subjectType}-id` );
							assert.include( response.body.message, subjectId );
						} );

						it( `responds ${subjectType}-not-found if ${subjectType} does not exist`, async () => {
							const subjectId = `${testData.subjectId}999`;
							const response = await newRequestBuilder( { subjectId, statementId: testData.statementId } )
								.assertValidRequest()
								.makeRequest();

							assertValidErrorResponse( response, 404, `${subjectType}-not-found` );
							assert.include( response.body.message, subjectId );
						} );

						it( `responds ${subjectType}-not-found if ${subjectType} and statement do not exist but` +
							'statement prefix does', async () => {
							const subjectId = `${testData.subjectId}999`;
							const statementId = `${testData.subjectId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
							const response = await newRequestBuilder( { subjectId, statementId } )
								.assertValidRequest()
								.makeRequest();

							assertValidErrorResponse( response, 404, `${subjectType}-not-found` );
							assert.include( response.body.message, subjectId );
						} );

						it( `responds ${subjectType}-not-found if ${subjectType}, statement, or statement prefix` +
							'do not exist', async () => {
							const subjectId = `${testData.subjectId}999`;
							const statementId = `${subjectId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
							const response = await newRequestBuilder( { subjectId, statementId } )
								.assertValidRequest()
								.makeRequest();

							assertValidErrorResponse( response, 404, `${subjectType}-not-found` );
							assert.include( response.body.message, subjectId );
						} );

						it( `responds statement-not-found if ${subjectType} exists` +
							' but statement prefix does not', async () => {
							const statementId = `${testData.subjectId}999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
							const response = await newRequestBuilder( { subjectId: testData.subjectId, statementId } )
								.assertValidRequest()
								.makeRequest();

							assertValidErrorResponse( response, 404, 'statement-not-found' );
							assert.include( response.body.message, statementId );
						} );

						it( `responds statement-not-found if ${subjectType} and statement prefix exists` +
							'but statement does not', async () => {
							const statementId = `${testData.subjectId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
							const response = await newRequestBuilder( { subjectId: testData.subjectId, statementId } )
								.assertValidRequest()
								.makeRequest();

							assertValidErrorResponse( response, 404, 'statement-not-found' );
							assert.include( response.body.message, statementId );
						} );

						it( `responds statement-not-found if ${subjectType} and statement exist,` +
							'but do not match', async () => {
							const requestInput = {
								subjectId: testData.otherSubjectId,
								statementId: testData.statementId
							};
							const response = await newRequestBuilder( requestInput )
								.assertValidRequest()
								.makeRequest();

							assertValidErrorResponse( response, 404, 'statement-not-found' );
							assert.include( response.body.message, testData.statementId );
						} );
					} );
				}

				if ( requestType === 'short' ) {
					describe( 'short route specific tests', () => {
						it( `responds statement-not-found if ${subjectType} not found`, async () => {
							const subjectId = testData.subjectId;
							const statementId = `${subjectId}999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
							const response = await newRequestBuilder( { subjectId, statementId } )
								.assertValidRequest()
								.makeRequest();

							assertValidErrorResponse( response, 404, 'statement-not-found' );
							assert.include( response.body.message, statementId );
						} );
					} );
				}
			} );
		}
	}
} );
