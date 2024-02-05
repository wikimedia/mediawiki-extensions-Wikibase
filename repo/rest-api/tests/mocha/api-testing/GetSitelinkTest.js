'use strict';

const { newGetSitelinkRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { utils, assert } = require( 'api-testing' );
const {
	createEntity,
	getLocalSiteId,
	createLocalSitelink,
	createRedirectForItem
} = require( '../helpers/entityHelper' );
const { expect } = require( '../helpers/chaiHelper' );

describe( newGetSitelinkRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let badgeItemId;
	let siteId;
	const linkedArticle = utils.title( 'Article-linked-to-test-item' );

	before( async () => {
		const createItemResponse = await createEntity( 'item', {} );
		const createBadgeItemResponse = await createEntity( 'item', {} );
		testItemId = createItemResponse.entity.id;
		badgeItemId = createBadgeItemResponse.entity.id;

		await createLocalSitelink( testItemId, linkedArticle, [ badgeItemId ] );
		siteId = await getLocalSiteId();
	} );

	it( 'can GET a single sitelink of an item', async () => {
		const response = await newGetSitelinkRequestBuilder( testItemId, siteId )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.equal( response.body.title, linkedArticle );
		assert.include( response.body.url, linkedArticle );
		assert.deepEqual( response.body.badges, [ badgeItemId ] );
	} );

	describe( '400 invalid request', () => {
		it( 'invalid item ID', async () => {
			const invalidItemId = 'X123';
			const response = await newGetSitelinkRequestBuilder( invalidItemId, siteId )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.strictEqual( response.body.code, 'invalid-item-id' );
			assert.include( response.body.message, invalidItemId );
		} );

		it( 'invalid site ID', async () => {
			const invalidSiteId = 'not-a-valid-site-id';
			const response = await newGetSitelinkRequestBuilder( testItemId, invalidSiteId )
				// .assertInvalidRequest() - valid per OAS because it only checks whether it is a string
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.strictEqual( response.body.code, 'invalid-site-id' );
			assert.include( response.body.message, invalidSiteId );
		} );
	} );

	describe( '404 resource not found', () => {
		it( 'item does not exist', async () => {
			const nonExistentItem = 'Q99999999';
			const response = await newGetSitelinkRequestBuilder( nonExistentItem, siteId )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.strictEqual( response.body.code, 'item-not-found' );
			assert.include( response.body.message, nonExistentItem );
		} );

		it( 'item has no sitelink with the requested site id', async () => {
			const item = await createEntity( 'item', {} );
			const response = await newGetSitelinkRequestBuilder( item.entity.id, siteId )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.strictEqual( response.body.code, 'sitelink-not-defined' );
			assert.include( response.body.message, siteId );
		} );
	} );

	it( '308 - item redirected', async () => {
		const redirectTarget = testItemId;
		const redirectSource = await createRedirectForItem( redirectTarget );

		const response = await newGetSitelinkRequestBuilder( redirectSource, siteId )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 308 );

		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `rest.php/wikibase/v0/entities/items/${redirectTarget}/sitelinks/${siteId}` )
		);
	} );

} );
