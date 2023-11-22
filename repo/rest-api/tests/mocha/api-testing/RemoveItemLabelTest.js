'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newRemoveItemLabelRequestBuilder,
	newSetItemLabelRequestBuilder,
	newGetItemLabelRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { formatTermEditSummary } = require( '../helpers/formatEditSummaries' );

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

describe( newRemoveItemLabelRequestBuilder().getRouteDescription(), () => {

	let testItemId;

	before( async () => {
		testItemId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
	} );

	describe( '200 success response', () => {
		it( 'can remove a label', async () => {
			const languageCode = 'en';
			const label = 'english label ' + utils.uniq();
			await newSetItemLabelRequestBuilder( testItemId, languageCode, label ).makeRequest();

			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const comment = 'remove english label';

			const response = await newRemoveItemLabelRequestBuilder( testItemId, languageCode )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body, 'Label deleted' );
			assert.header( response, 'Content-Language', languageCode );

			const verifyDeleted = await newGetItemLabelRequestBuilder( testItemId, languageCode ).makeRequest();
			expect( verifyDeleted ).to.have.status( 404 );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatTermEditSummary( 'wbsetlabel', 'remove', languageCode, label, comment )
			);
		} );
	} );

	describe( '400 error response', () => {
		it( 'invalid item id', async () => {
			const itemId = testItemId.replace( 'Q', 'P' );
			const response = await newRemoveItemLabelRequestBuilder( itemId, 'en' )
				.assertInvalidRequest().makeRequest();

			assertValidErrorResponse( response, 400, 'invalid-item-id' );
			assert.include( response.body.message, itemId );
		} );

		it( 'invalid language code', async () => {
			const invalidLanguageCode = 'xyz';
			const response = await newRemoveItemLabelRequestBuilder( testItemId, invalidLanguageCode )
				.assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 400, 'invalid-language-code' );
			assert.include( response.body.message, invalidLanguageCode );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newRemoveItemLabelRequestBuilder( testItemId, 'en' )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 400, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newRemoveItemLabelRequestBuilder( testItemId, 'en' )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newRemoveItemLabelRequestBuilder( testItemId, 'en' )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newRemoveItemLabelRequestBuilder( testItemId, 'en' )
				.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 400, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newRemoveItemLabelRequestBuilder( testItemId, 'en' )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'comment' );
			assert.strictEqual( response.body.expectedType, 'string' );
		} );
	} );

	describe( '404 error response', () => {
		it( 'item not found', async () => {
			const itemId = 'Q999999';
			const response = await newRemoveItemLabelRequestBuilder( itemId, 'en' )
				.assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 404, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );

		it( 'label in the language specified does not exist', async () => {
			const languageCode = 'ar';
			const response = await newRemoveItemLabelRequestBuilder( testItemId, languageCode )
				.assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 404, 'label-not-defined' );
			assert.include( response.body.message, testItemId );
			assert.include( response.body.message, languageCode );
		} );
	} );

	describe( '409 error response', () => {
		it( 'item is a redirect', async () => {
			const redirectTarget = testItemId;
			const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

			const response = await newRemoveItemLabelRequestBuilder( redirectSource, 'en' )
				.assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 409, 'redirected-item' );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
		} );
	} );

} );
