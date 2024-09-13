'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newRemoveItemDescriptionRequestBuilder,
	newSetItemDescriptionRequestBuilder,
	newGetItemDescriptionRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { formatTermEditSummary } = require( '../helpers/formatEditSummaries' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );

describe( newRemoveItemDescriptionRequestBuilder().getRouteDescription(), () => {

	let testItemId;

	before( async () => {
		testItemId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
	} );

	describe( '200 success response', () => {
		it( 'can remove a description', async () => {
			const languageCode = 'en';
			const description = 'english description ' + utils.uniq();
			await newSetItemDescriptionRequestBuilder( testItemId, languageCode, description ).makeRequest();

			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
			const comment = 'remove english description';

			const response = await newRemoveItemDescriptionRequestBuilder( testItemId, languageCode )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body, 'Description deleted' );
			assert.header( response, 'Content-Language', languageCode );

			const verifyDeleted = await newGetItemDescriptionRequestBuilder( testItemId, languageCode ).makeRequest();
			expect( verifyDeleted ).to.have.status( 404 );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatTermEditSummary( 'wbsetdescription', 'remove', languageCode, description, comment )
			);
		} );
	} );

	describe( '400 error response', () => {
		it( 'invalid item id', async () => {
			const itemId = testItemId.replace( 'Q', 'P' );
			const response = await newRemoveItemDescriptionRequestBuilder( itemId, 'en' )
				.assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'item_id' }
			);
		} );

		it( 'invalid language code', async () => {
			const response = await newRemoveItemDescriptionRequestBuilder( testItemId, 'xyz' )
				.assertValidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'language_code' }
			);
		} );
	} );

	describe( '404 error response', () => {
		it( 'item not found', async () => {
			const itemId = 'Q999999';
			const response = await newRemoveItemDescriptionRequestBuilder( itemId, 'en', 'test description' )
				.assertValidRequest().makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'item' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );

		it( 'description in the language specified does not exist', async () => {
			const languageCode = 'ar';
			const response = await newRemoveItemDescriptionRequestBuilder( testItemId, languageCode )
				.assertValidRequest().makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'description' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );
	} );

	describe( '409 error response', () => {
		it( 'item is a redirect', async () => {
			const redirectTarget = testItemId;
			const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

			const response = await newRemoveItemDescriptionRequestBuilder( redirectSource, 'en', 'test description' )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'redirected-item', { redirect_target: redirectTarget } );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
		} );
	} );
} );
