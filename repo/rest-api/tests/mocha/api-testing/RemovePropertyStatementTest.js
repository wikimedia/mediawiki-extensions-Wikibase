'use strict';

const { assert, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { formatStatementEditSummary } = require( '../helpers/formatEditSummaries' );
const {
	newAddPropertyStatementRequestBuilder,
	newRemovePropertyStatementRequestBuilder,
	newRemoveStatementRequestBuilder,
	newGetStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( 'DELETE statement', () => {

	let testPropertyId;
	let testStatementPropertyId;

	before( async () => {
		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
		testStatementPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
	} );

	[
		( statementId, patch ) => newRemovePropertyStatementRequestBuilder( testPropertyId, statementId, patch ),
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
					const user = await action.robby(); // robby is a bot
					const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
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
					const statementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newRemoveRequestBuilder( statementId )
						.assertInvalidRequest()
						.makeRequest();

					assertValidError( response, 400, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );

				it( 'statement ID is invalid format', async () => {
					const statementId = 'not-a-valid-format';
					const response = await newRemoveRequestBuilder( statementId )
						.assertInvalidRequest()
						.makeRequest();

					assertValidError( response, 400, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );
			} );

			describe( '404 statement not found', () => {
				it( 'responds 404 statement-not-found for nonexistent statement', async () => {
					const statementId = testPropertyId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newRemoveRequestBuilder( statementId )
						.assertValidRequest()
						.makeRequest();

					assertValidError( response, 404, 'statement-not-found' );
					assert.include( response.body.message, statementId );
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
			const requestedPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			const statementId = testStatementPropertyId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemovePropertyStatementRequestBuilder( requestedPropertyId, statementId )
				.assertValidRequest()
				.makeRequest();

			const context = { 'property-id': requestedPropertyId, 'statement-id': statementId };
			assertValidError( response, 400, 'property-statement-id-mismatch', context );
		} );

		it( 'responds 404 property-not-found for nonexistent property', async () => {
			const propertyId = 'P999999';
			const statementId = `${propertyId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newRemovePropertyStatementRequestBuilder( propertyId, statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );
	} );

	describe( 'short route specific errors', () => {
		it( 'responds 400 invalid-statement-id if statement is not on a supported entity', async () => {
			const statementId = 'L123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemoveStatementRequestBuilder( statementId )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-statement-id' );
			assert.include( response.body.message, statementId );
		} );

		it( 'responds 404 statement-not-found for nonexistent property', async () => {
			const statementId = 'P999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemoveStatementRequestBuilder( statementId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
	} );

} );
