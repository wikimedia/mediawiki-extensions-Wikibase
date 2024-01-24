'use strict';

const {
	newRemoveItemSiteLinkRequestBuilder,
	newGetItemSiteLinksRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { action, utils, assert } = require( 'api-testing' );
const { createEntity, getLocalSiteId, createLocalSiteLink } = require( '../helpers/entityHelper' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );

describe( newRemoveItemSiteLinkRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let siteId;
	const linkedArticle = utils.title( 'Article-linked-to-test-item' );

	before( async () => {
		siteId = await getLocalSiteId();

		const createItemResponse = await createEntity( 'item', {} );
		testItemId = createItemResponse.entity.id;

		await createLocalSiteLink( testItemId, linkedArticle );
	} );

	describe( '200', () => {
		afterEach( async () => {
			await createLocalSiteLink( testItemId, linkedArticle );
		} );

		it( 'can DELETE a single sitelink of an item', async () => {
			const response = await newRemoveItemSiteLinkRequestBuilder( testItemId, siteId )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.equal( response.body, 'Sitelink deleted' );

			const itemSiteLinks = ( await newGetItemSiteLinksRequestBuilder( testItemId ).makeRequest() ).body;
			assert.notProperty( itemSiteLinks, siteId );
		} );

		it( 'can DELETE a sitelink with edit metadata provided', async () => {
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const comment = 'removed a bad sitelink!';

			const response = await newRemoveItemSiteLinkRequestBuilder( testItemId, siteId )
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
			const itemWithNoSiteLink = ( await createEntity( 'item', {} ) ).entity.id;
			const response = await newRemoveItemSiteLinkRequestBuilder( itemWithNoSiteLink, siteId )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 404 );
			assert.strictEqual( response.body.code, 'sitelink-not-defined' );
			assert.include( response.body.message, itemWithNoSiteLink );
			assert.include( response.body.message, siteId );
		} );
		it( 'responds with 404 if the item does not exist', async () => {
			const itemDoesNotExist = 'Q999999';
			const response = await newRemoveItemSiteLinkRequestBuilder( itemDoesNotExist, siteId )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 404 );
			assert.strictEqual( response.body.code, 'item-not-found' );
			assert.include( response.body.message, itemDoesNotExist );
		} );
	} );

	it( 'responds 409 if the item is a redirect', async () => {
		const redirectSource = await entityHelper.createRedirectForItem( testItemId );
		const response = await newRemoveItemSiteLinkRequestBuilder( redirectSource, siteId )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 409 );
		assert.strictEqual( response.body.code, 'redirected-item' );
		assert.include( response.body.message, redirectSource );

	} );

	describe( '400', () => {
		it( 'invalid item ID', async () => {
			const invalidItemId = 'X123';
			const response = await newRemoveItemSiteLinkRequestBuilder( invalidItemId, siteId )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.strictEqual( response.body.code, 'invalid-item-id' );
			assert.include( response.body.message, invalidItemId );
		} );

		// From OAS point of view, this request is valid, as there is no specific pattern for the site id string,
		// but site id can't be any string, so from Wikibase point of view it's invalid
		// that's why the 'assertInvalidRequest' method was commented here.
		it( 'invalid site ID', async () => {
			const invalidSiteId = 'not-a-valid-site-id';
			const response = await newRemoveItemSiteLinkRequestBuilder( testItemId, invalidSiteId )
				// .assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.strictEqual( response.body.code, 'invalid-site-id' );
			assert.include( response.body.message, invalidSiteId );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newRemoveItemSiteLinkRequestBuilder( testItemId, siteId )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newRemoveItemSiteLinkRequestBuilder( testItemId, siteId )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newRemoveItemSiteLinkRequestBuilder( testItemId, siteId )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newRemoveItemSiteLinkRequestBuilder( testItemId, siteId )
				.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newRemoveItemSiteLinkRequestBuilder( testItemId, siteId )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'comment' );
			assert.strictEqual( response.body.expectedType, 'string' );
		} );
	} );

	it( 'responds 415 for unsupported media type', async () => {
		const contentType = 'multipart/form-data';
		const response = await newRemoveItemSiteLinkRequestBuilder( testItemId, siteId )
			.withHeader( 'content-type', contentType )
			.makeRequest();

		expect( response ).to.have.status( 415 );
		assert.strictEqual( response.body.message, `Unsupported Content-Type: '${contentType}'` );
	} );
} );
