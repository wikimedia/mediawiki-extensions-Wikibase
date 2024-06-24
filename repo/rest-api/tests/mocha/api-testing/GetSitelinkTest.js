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
const { getAllowedBadges } = require( '../helpers/getAllowedBadges' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newGetSitelinkRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let badgeItemId;
	let siteId;
	const linkedArticle = utils.title( 'Article-linked-to-test-item' );

	before( async () => {
		const createItemResponse = await createEntity( 'item', {} );
		testItemId = createItemResponse.entity.id;
		badgeItemId = ( await getAllowedBadges() )[ 0 ];

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

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'item_id' }
			);
		} );

		it( 'invalid site ID', async () => {
			const response = await newGetSitelinkRequestBuilder( testItemId, 'not-a-valid-site-id' )
				// .assertInvalidRequest() - valid per OAS because it only checks whether it is a string
				.makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'site_id' }
			);
		} );
	} );

	describe( '404 resource not found', () => {
		it( 'item does not exist', async () => {
			const nonExistentItem = 'Q99999999';
			const response = await newGetSitelinkRequestBuilder( nonExistentItem, siteId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'item-not-found' );
			assert.include( response.body.message, nonExistentItem );
		} );

		it( 'item has no sitelink with the requested site id', async () => {
			const item = await createEntity( 'item', {} );
			const response = await newGetSitelinkRequestBuilder( item.entity.id, siteId )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'sitelink-not-defined' );
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
