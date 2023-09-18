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

					expect( response ).to.have.status( 400 );
					assert.header( response, 'Content-Language', 'en' );
					assert.equal( response.body.code, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );

				it( 'statement ID is invalid format', async () => {
					const statementId = 'not-a-valid-format';
					const response = await newRemoveRequestBuilder( statementId )
						.assertInvalidRequest()
						.makeRequest();

					expect( response ).to.have.status( 400 );
					assert.header( response, 'Content-Language', 'en' );
					assert.equal( response.body.code, 'invalid-statement-id' );
					assert.include( response.body.message, statementId );
				} );
			} );

			describe( '404 statement not found', () => {
				it( 'responds 404 statement-not-found for nonexistent statement', async () => {
					const statementId = testPropertyId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newRemoveRequestBuilder( statementId )
						.assertValidRequest()
						.makeRequest();

					expect( response ).to.have.status( 404 );
					assert.header( response, 'Content-Language', 'en' );
					assert.equal( response.body.code, 'statement-not-found' );
					assert.include( response.body.message, statementId );
				} );
			} );

			describe( '415 error response', () => {
				it( 'unsupported media type', async () => {
					const contentType = 'multipart/form-data';
					const response = await newRemoveRequestBuilder( 'id-does-not-matter' )
						.withHeader( 'content-type', contentType )
						.makeRequest();

					expect( response ).to.have.status( 415 );
					assert.strictEqual( response.body.message, `Unsupported Content-Type: '${contentType}'` );
				} );
			} );

		} );
	} );

	describe( 'long route specific errors', () => {
		it( 'responds 400 for invalid property ID', async () => {
			const propertyId = 'X123';
			const statementId = 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemovePropertyStatementRequestBuilder( propertyId, statementId )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'invalid-property-id' );
			assert.include( response.body.message, propertyId );
		} );

		it( 'responds 404 property-not-found for nonexistent property', async () => {
			const propertyId = 'P999999';
			const statementId = `${propertyId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newRemovePropertyStatementRequestBuilder( propertyId, statementId )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );

		it( 'responds 404 statement-not-found for property ID mismatch', async () => {
			const statementId = 'P1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response =
				await newRemovePropertyStatementRequestBuilder( testPropertyId, statementId )
					.assertValidRequest()
					.makeRequest();

			expect( response ).to.have.status( 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
	} );

	describe( 'short route specific errors', () => {
		it( 'responds 400 invalid-statement-id if statement is not on a supported entity', async () => {
			const statementId = 'L123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemoveStatementRequestBuilder( statementId )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'invalid-statement-id' );
			assert.include( response.body.message, statementId );
		} );

		it( 'responds 404 statement-not-found for nonexistent property', async () => {
			const statementId = 'P999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemoveStatementRequestBuilder( statementId )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
	} );

} );
