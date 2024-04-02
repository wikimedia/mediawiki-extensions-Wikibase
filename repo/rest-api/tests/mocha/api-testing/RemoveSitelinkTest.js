'use strict';

const {
	newRemoveSitelinkRequestBuilder,
	newGetSitelinksRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { action, utils, assert } = require( 'api-testing' );
const { createEntity, getLocalSiteId, createLocalSitelink } = require( '../helpers/entityHelper' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newRemoveSitelinkRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let siteId;
	const linkedArticle = utils.title( 'Article-linked-to-test-item' );

	before( async () => {
		siteId = await getLocalSiteId();

		const createItemResponse = await createEntity( 'item', {} );
		testItemId = createItemResponse.entity.id;

		await createLocalSitelink( testItemId, linkedArticle );
	} );

	describe( '200', () => {
		afterEach( async () => {
			await createLocalSitelink( testItemId, linkedArticle );
		} );

		it( 'can DELETE a single sitelink of an item', async () => {
			const response = await newRemoveSitelinkRequestBuilder( testItemId, siteId )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.equal( response.body, 'Sitelink deleted' );

			const sitelinks = ( await newGetSitelinksRequestBuilder( testItemId ).makeRequest() ).body;
			assert.notProperty( sitelinks, siteId );
		} );

		it( 'can DELETE a sitelink with edit metadata provided', async () => {
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const comment = 'removed a bad sitelink!';

			const response = await newRemoveSitelinkRequestBuilder( testItemId, siteId )
				.withUser( user )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				`/* wbsetsitelink-remove:1|${siteId} */ ${linkedArticle}, ${comment}`
			);
		} );
	} );

	describe( '404', () => {
		it( 'responds 404 if there is no sitelink for the requested site', async () => {
			const itemWithNoSitelink = ( await createEntity( 'item', {} ) ).entity.id;
			const response = await newRemoveSitelinkRequestBuilder( itemWithNoSitelink, siteId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'sitelink-not-defined' );
			assert.include( response.body.message, itemWithNoSitelink );
			assert.include( response.body.message, siteId );
		} );
		it( 'responds with 404 if the item does not exist', async () => {
			const itemDoesNotExist = 'Q999999';
			const response = await newRemoveSitelinkRequestBuilder( itemDoesNotExist, siteId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'item-not-found' );
			assert.include( response.body.message, itemDoesNotExist );
		} );
	} );

	it( 'responds 409 if the item is a redirect', async () => {
		const redirectSource = await entityHelper.createRedirectForItem( testItemId );
		const response = await newRemoveSitelinkRequestBuilder( redirectSource, siteId )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 409, 'redirected-item' );
		assert.include( response.body.message, redirectSource );

	} );

	describe( '400', () => {
		it( 'invalid item ID', async () => {
			const invalidItemId = 'X123';
			const response = await newRemoveSitelinkRequestBuilder( invalidItemId, siteId )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-item-id' );
			assert.include( response.body.message, invalidItemId );
		} );

		it( 'invalid site ID', async () => {
			const invalidSiteId = 'not-a-valid-site-id';
			const response = await newRemoveSitelinkRequestBuilder( testItemId, invalidSiteId )
				// .assertInvalidRequest() - valid per OAS because it only checks whether it is a string
				.makeRequest();

			assertValidError( response, 400, 'invalid-site-id' );
			assert.include( response.body.message, invalidSiteId );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newRemoveSitelinkRequestBuilder( testItemId, siteId )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newRemoveSitelinkRequestBuilder( testItemId, siteId )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newRemoveSitelinkRequestBuilder( testItemId, siteId )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newRemoveSitelinkRequestBuilder( testItemId, siteId )
				.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newRemoveSitelinkRequestBuilder( testItemId, siteId )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'comment' );
			assert.strictEqual( response.body.expectedType, 'string' );
		} );
	} );
} );
