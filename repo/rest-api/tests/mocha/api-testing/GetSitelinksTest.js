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
const { getAllowedBadges } = require( '../helpers/getAllowedBadges' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newGetSitelinksRequestBuilder().getRouteDescription(), () => {

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

		assertValidError(
			response,
			400,
			'invalid-path-parameter',
			{ parameter: 'item_id' }
		);
	} );

	it( 'responds 404 in case the item does not exist', async () => {
		const nonExistentItem = 'Q99999999';
		const response = await newGetSitelinksRequestBuilder( nonExistentItem )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'item' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
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
				.endsWith( `rest.php/wikibase/v1/entities/items/${redirectTarget}/sitelinks` )
		);
	} );

} );
