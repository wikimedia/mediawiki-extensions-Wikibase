'use strict';

const { newGetItemSiteLinksRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { action, utils, assert } = require( 'api-testing' );
const { createEntity } = require( '../helpers/entityHelper' );
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

} );
