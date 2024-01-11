'use strict';

const {
	newRemoveItemSiteLinkRequestBuilder,
	newGetItemSiteLinksRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { action, utils, assert } = require( 'api-testing' );
const { createEntity } = require( '../helpers/entityHelper' );
const { expect } = require( '../helpers/chaiHelper' );

describe( newRemoveItemSiteLinkRequestBuilder().getRouteDescription(), () => {

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

	it( 'can DELETE a single sitelink of an item', async () => {
		const response = await newRemoveItemSiteLinkRequestBuilder( testItemId, siteId )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.equal( response.body, 'Sitelink deleted' );

		const itemSiteLinks = ( await newGetItemSiteLinksRequestBuilder( testItemId ).makeRequest() ).body;
		assert.notProperty( itemSiteLinks, siteId );
	} );

} );
