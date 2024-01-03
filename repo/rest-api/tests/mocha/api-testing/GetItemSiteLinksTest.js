'use strict';

const { newGetItemSiteLinksRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { action, utils, assert } = require( 'api-testing' );
const { createEntity, createRedirectForItem } = require( '../helpers/entityHelper' );
const { expect } = require( '../helpers/chaiHelper' );

describe( newGetItemSiteLinksRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let siteId;
	const linkedArticle = utils.title( 'Article-linked-to-test-item' );

	before( async () => {
		siteId = ( await action.getAnon().meta(
			'wikibase',
			{ wbprop: 'siteid' }
		) ).siteid;
		await action.getAnon().edit( linkedArticle, { text: 'sitelink test' } );

		const createItemResponse = await createEntity( 'item', {
			sitelinks: {
				[ siteId ]: {
					site: siteId,
					title: linkedArticle
				}
			}
		} );
		testItemId = createItemResponse.entity.id;
	} );

	it( 'can GET sitelinks of an item', async () => {
		const response = await newGetItemSiteLinksRequestBuilder( testItemId )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.equal( response.body[ siteId ].title, linkedArticle );
		assert.include( response.body[ siteId ].url, linkedArticle );
	} );

	it( 'can GET empty object if no sitelinks exist', async () => {
		const item = await createEntity( 'item', {} );

		const response = await newGetItemSiteLinksRequestBuilder( item.entity.id )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual( response.body, {} );
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const invalidItemId = 'X123';
		const response = await newGetItemSiteLinksRequestBuilder( invalidItemId )
			.assertInvalidRequest()
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'invalid-item-id' );
		assert.include( response.body.message, invalidItemId );
	} );

	it( 'responds 404 in case the item does not exist', async () => {
		const nonExistentItem = 'Q99999999';
		const response = await newGetItemSiteLinksRequestBuilder( nonExistentItem )
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

		const response = await newGetItemSiteLinksRequestBuilder( redirectSource )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 308 );

		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `rest.php/wikibase/v0/entities/items/${redirectTarget}/sitelinks` )
		);
	} );

} );
