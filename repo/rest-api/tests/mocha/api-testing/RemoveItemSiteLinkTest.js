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

			const response = await newRemoveItemSiteLinkRequestBuilder( testItemId, siteId )
				.withUser( user )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'tags', [ tag ] )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
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
	} );

} );
