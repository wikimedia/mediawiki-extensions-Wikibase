'use strict';

const { createEntity, getLatestEditMetadata, createRedirectForItem } = require( '../helpers/entityHelper' );
const { newGetItemLabelsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { assert } = require( 'api-testing' );

describe( 'GET /entities/items/{id}/labels', () => {
	let itemId;

	before( async () => {
		const createItemResponse = await createEntity( 'item', {
			labels: {
				en: { language: 'en', value: 'potato' }
			}
		} );

		itemId = createItemResponse.entity.id;
	} );

	it( 'can get the labels of an item', async () => {
		const testItemCreationMetadata = await getLatestEditMetadata( itemId );

		const response = await newGetItemLabelsRequestBuilder( itemId ).assertValidRequest().makeRequest();

		assert.strictEqual( response.status, 200 );
		assert.deepEqual( response.body, { en: 'potato' } );
		assert.strictEqual( response.header.etag, `"${testItemCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testItemCreationMetadata.timestamp );
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const invalidItemId = 'X123';
		const response = await newGetItemLabelsRequestBuilder( invalidItemId )
			.assertInvalidRequest()
			.makeRequest();

		assert.strictEqual( response.status, 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'invalid-item-id' );
		assert.include( response.body.message, invalidItemId );
	} );

	it( 'responds 404 in case the item does not exist', async () => {
		const nonExistentItem = 'Q99999999';
		const response = await newGetItemLabelsRequestBuilder( nonExistentItem ).makeRequest();

		assert.strictEqual( response.status, 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'item-not-found' );
		assert.include( response.body.message, nonExistentItem );
	} );

	it( '308 - item redirected', async () => {
		const redirectTarget = itemId;
		const redirectSource = await createRedirectForItem( redirectTarget );

		const response = await newGetItemLabelsRequestBuilder( redirectSource )
			.assertValidRequest()
			.makeRequest();

		assert.strictEqual( response.status, 308 );

		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `rest.php/wikibase/v0/entities/items/${redirectTarget}/labels` )
		);
	} );
} );
