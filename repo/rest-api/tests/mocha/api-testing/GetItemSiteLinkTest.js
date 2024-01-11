'use strict';

const { newGetItemSiteLinkRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { utils, assert } = require( 'api-testing' );
const { createEntity, getLocalSiteId, createLocalSiteLink } = require( '../helpers/entityHelper' );
const { expect } = require( '../helpers/chaiHelper' );

describe( newGetItemSiteLinkRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let siteId;
	const linkedArticle = utils.title( 'Article-linked-to-test-item' );

	before( async () => {
		const createItemResponse = await createEntity( 'item', {} );
		testItemId = createItemResponse.entity.id;

		await createLocalSiteLink( testItemId, linkedArticle );
		siteId = await getLocalSiteId();
	} );

	it( 'can GET a single sitelink of an item', async () => {
		const response = await newGetItemSiteLinkRequestBuilder( testItemId, siteId )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.equal( response.body.title, linkedArticle );
		assert.include( response.body.url, linkedArticle );
	} );

} );
