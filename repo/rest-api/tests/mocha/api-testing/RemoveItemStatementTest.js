'use strict';

const { assert, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { formatStatementEditSummary } = require( '../helpers/formatEditSummaries' );
const {
	newAddItemStatementRequestBuilder,
	newRemoveItemStatementRequestBuilder,
	newRemoveStatementRequestBuilder,
	newGetStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( 'DELETE statement', () => {

	let testItemId;
	let testStatementPropertyId;

	before( async () => {
		testItemId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
		testStatementPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
	} );

	[
		( statementId, patch ) => newRemoveItemStatementRequestBuilder( testItemId, statementId, patch ),
		newRemoveStatementRequestBuilder
	].forEach( ( newRemoveRequestBuilder ) => {
		describe( newRemoveRequestBuilder().getRouteDescription(), () => {

			describe( '200 success response', () => {
				let testStatement;

				async function addStatementWithRandomStringValue( itemId, statementPropertyId ) {
					return ( await newAddItemStatementRequestBuilder(
						itemId,
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
					testStatement = await addStatementWithRandomStringValue( testItemId, testStatementPropertyId );
				} );

				it( 'can remove a statement without request body', async () => {
					const response =
						await newRemoveRequestBuilder( testStatement.id )
							.assertValidRequest()
							.makeRequest();

					assertValid200Response( response );
					const { comment } = await entityHelper.getLatestEditMetadata( testItemId );
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

					const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
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
					const statementId = testItemId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newRemoveRequestBuilder( statementId )
						.assertValidRequest()
						.makeRequest();

					expect( response ).to.have.status( 404 );
					assert.header( response, 'Content-Language', 'en' );
					assert.equal( response.body.code, 'statement-not-found' );
					assert.include( response.body.message, statementId );
				} );

				it( 'statement subject is a redirect', async () => {
					const redirectSource = await entityHelper.createRedirectForItem( testItemId );
					const statementId = redirectSource + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
					const response = await newRemoveRequestBuilder( statementId )
						.assertValidRequest()
						.makeRequest();

					expect( response ).to.have.status( 404 );
					assert.header( response, 'Content-Language', 'en' );
					assert.equal( response.body.code, 'statement-not-found' );
					assert.include( response.body.message, statementId );
				} );
			} );
		} );
	} );

	describe( 'long route specific errors', () => {
		it( 'responds 400 for invalid item ID', async () => {
			const itemId = 'X123';
			const statementId = 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newRemoveItemStatementRequestBuilder( itemId, statementId )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'invalid-item-id' );
			assert.include( response.body.message, itemId );
		} );

		it( 'responds 404 item-not-found for nonexistent item', async () => {
			const itemId = 'Q999999';
			const statementId = `${itemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newRemoveItemStatementRequestBuilder( itemId, statementId )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );

		it( 'responds 404 statement-not-found for item ID mismatch', async () => {
			const statementId = 'Q1$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response =
				await newRemoveItemStatementRequestBuilder( testItemId, statementId )
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

		it( 'responds 404 statement-not-found for nonexistent item', async () => {
			const statementId = 'Q999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
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
