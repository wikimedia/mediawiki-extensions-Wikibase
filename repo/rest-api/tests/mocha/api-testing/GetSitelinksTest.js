'use strict';

const { newGetSitelinksRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { utils, assert } = require( 'api-testing' );
const {
	createEntity,
	createRedirectForItem,
	getLocalSiteId,
	createLocalSitelink
} = require( '../helpers/entityHelper' );
const { expect } = require( '../helpers/chaiHelper' );

describe( newGetSitelinksRequestBuilder().getRouteDescription(), () => {

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

	it( 'can GET sitelinks of an item', async () => {
		const response = await newGetSitelinksRequestBuilder( testItemId )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.equal( response.body[ siteId ].title, linkedArticle );
		assert.include( response.body[ siteId ].url, linkedArticle );
		assert.deepEqual( response.body[ siteId ].badges, [ badgeItemId ] );
	} );

	it( 'can GET empty object if no sitelinks exist', async () => {
		const item = await createEntity( 'item', {} );

		const response = await newGetSitelinksRequestBuilder( item.entity.id )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual( response.body, {} );
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const invalidItemId = 'X123';
		const response = await newGetSitelinksRequestBuilder( invalidItemId )
			.assertInvalidRequest()
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'invalid-item-id' );
		assert.include( response.body.message, invalidItemId );
	} );

	it( 'responds 404 in case the item does not exist', async () => {
		const nonExistentItem = 'Q99999999';
		const response = await newGetSitelinksRequestBuilder( nonExistentItem )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'item-not-found' );
		assert.include( response.body.message, nonExistentItem );
	} );

	it( '308 - item redirected', async () => {
		const redirectTarget = testItemId;
		const redirectSource = await createRedirectForItem( redirectTarget );

		const response = await newGetSitelinksRequestBuilder( redirectSource )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 308 );

		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `rest.php/wikibase/v0/entities/items/${redirectTarget}/sitelinks` )
		);
	} );

} );
