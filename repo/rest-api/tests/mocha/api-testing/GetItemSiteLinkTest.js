'use strict';

const { newGetItemSiteLinkRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { action, utils, assert } = require( 'api-testing' );
const { createEntity } = require( '../helpers/entityHelper' );
const { expect } = require( '../helpers/chaiHelper' );

describe( newGetItemSiteLinkRequestBuilder().getRouteDescription(), () => {

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

	it( 'can GET a single sitelink of an item', async () => {
		const response = await newGetItemSiteLinkRequestBuilder( testItemId, siteId )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.equal( response.body.title, linkedArticle );
		assert.include( response.body.url, linkedArticle );
	} );

	describe( '400 invalid request', () => {
		it( 'invalid item ID', async () => {
			const invalidItemId = 'X123';
			const response = await newGetItemSiteLinkRequestBuilder( invalidItemId, siteId )
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
			const response = await newGetItemSiteLinkRequestBuilder( testItemId, invalidSiteId )
				// .assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.strictEqual( response.body.code, 'invalid-site-id' );
			assert.include( response.body.message, invalidSiteId );
		} );
	} );

} );
