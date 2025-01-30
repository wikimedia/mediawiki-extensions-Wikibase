'use strict';

const { assert, action } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { formatStatementEditSummary } = require( '../helpers/formatEditSummaries' );
const {
	newAddPropertyStatementRequestBuilder,
	newRemovePropertyStatementRequestBuilder,
	newRemoveStatementRequestBuilder,
	newGetStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );

describe( 'DELETE statement', () => {

	let testPropertyId;
	let testStatementPropertyId;

	before( async () => {
		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).body.id;
		testStatementPropertyId = await entityHelper.getStringPropertyId();
	} );

	[
		( statementId ) => newRemovePropertyStatementRequestBuilder( testPropertyId, statementId ),
		newRemoveStatementRequestBuilder
	].forEach( ( newRemoveRequestBuilder ) => {
		describe( newRemoveRequestBuilder().getRouteDescription(), () => {

			describe( '200 success response', () => {
				let testStatement;

				async function addStatementWithRandomStringValue( propertyId, statementPropertyId ) {
					return ( await newAddPropertyStatementRequestBuilder(
						propertyId,
						entityHelper.newStatementWithRandomStringValue( statementPropertyId )
					).makeRequest() ).body;
				}

				async function verifyStatementDeleted( statementId ) {
					const verifyStatement = await newGetStatementRequestBuilder( statementId ).makeRequest();

					expect( verifyStatement ).to.have.status( 404 );

				}

				function assertValid200Response( response ) {
					expect( response ).to.have.status( 200 );
					assert.equal( response.body, 'Statement deleted' );
				}

				beforeEach( async () => {
					testStatement = await addStatementWithRandomStringValue( testPropertyId, testStatementPropertyId );
				} );

				it( 'can remove a statement without request body', async () => {
					const response =
						await newRemoveRequestBuilder( testStatement.id )
							.assertValidRequest()
							.makeRequest();

					assertValid200Response( response );
					const { comment } = await entityHelper.getLatestEditMetadata( testPropertyId );
					assert.strictEqual(
						comment,
						formatStatementEditSummary(
							'wbremoveclaims',
							'remove',
							testStatement.property.id,
							testStatement.value.content
						)
					);
					await verifyStatementDeleted( testStatement.id );
				} );

				it( 'can remove a statement with edit metadata provided', async () => {
					const user = await getOrCreateBotUser();
					const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
					const editSummary = 'omg look i removed a statement';
					const response =
						await newRemoveRequestBuilder( testStatement.id )
							.withJsonBodyParam( 'tags', [ tag ] )
							.withJsonBodyParam( 'bot', true )
							.withJsonBodyParam( 'comment', editSummary )
							.withUser( user )
							.assertValidRequest()
							.makeRequest();

					assertValid200Response( response );
					await verifyStatementDeleted( testStatement.id );

					const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
					assert.include( editMetadata.tags, tag );
					assert.property( editMetadata, 'bot' );
					assert.strictEqual(
						editMetadata.comment,
						formatStatementEditSummary( 'wbremoveclaims',
							'remove',
							testStatement.property.id,
							testStatement.value.content,
							editSummary
						)
					);
					assert.strictEqual( editMetadata.user, user.username );
				} );
			} );

			describe( '400 error response', () => {
				it( 'statement ID contains invalid entity ID', async () => {
					const response = await newRemoveRequestBuilder( 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
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
					const response = await newRemoveRequestBuilder( 'not-a-valid-format' )
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

			describe( '404 statement not found', () => {
				it( 'responds 404 statement not found for nonexistent statement', async () => {
					const statementId = testPropertyId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newRemoveRequestBuilder( statementId )
						.assertValidRequest()
						.makeRequest();

					assertValidError( response, 404, 'resource-not-found', { resource_type: 'statement' } );
					assert.strictEqual( response.body.message, 'The requested resource does not exist' );
				} );
			} );
		} );
	} );

	describe( 'long route specific errors', () => {
		it( 'responds 400 for invalid property ID', async () => {
			const statementId = 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemovePropertyStatementRequestBuilder( 'X123', statementId )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'property_id' }
			);
		} );

		it( 'responds 400 if property and statement do not match', async () => {
			const requestedPropertyId = ( await entityHelper.createUniqueStringProperty() ).body.id;
			const statementId = testStatementPropertyId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemovePropertyStatementRequestBuilder( requestedPropertyId, statementId )
				.assertValidRequest()
				.makeRequest();

			const context = { property_id: requestedPropertyId, statement_id: statementId };
			assertValidError( response, 400, 'property-statement-id-mismatch', context );
		} );

		it( 'responds 404 property-not-found for nonexistent property', async () => {
			const propertyId = 'P999999';
			const statementId = `${propertyId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newRemovePropertyStatementRequestBuilder( propertyId, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'property' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );
	} );

	describe( 'short route specific errors', () => {
		it( 'responds 400 invalid-statement-id if statement is not on a supported entity', async () => {
			const response = await newRemoveStatementRequestBuilder( 'L123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'statement_id' }
			);
		} );

		it( 'responds 404 statement not found for nonexistent property', async () => {
			const statementId = 'P999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemoveStatementRequestBuilder( statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'statement' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );
	} );

} );
