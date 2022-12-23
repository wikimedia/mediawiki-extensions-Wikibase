'use strict';

const { createEntity, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { newGetItemLabelsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { assert } = require( 'api-testing' );

describe( 'GET /entities/items/{id}/labels', () => {
	it( 'can get the labels of an item', async () => {
		const createItemResponse = await createEntity( 'item', {
			labels: {
				en: {
					language: 'en',
					value: 'potato'
				}
			}
		} );

		const itemId = createItemResponse.entity.id;
		const testItemCreationMetadata = await getLatestEditMetadata( itemId );

		const response = await newGetItemLabelsRequestBuilder( itemId ).makeRequest();

		assert.strictEqual( response.status, 200 );
		assert.deepEqual( response.body, { en: 'potato' } );
		assert.strictEqual( response.header.etag, `"${testItemCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testItemCreationMetadata.timestamp );
	} );

	it( 'responds 404 in case the item does not exist', async () => {
		const nonExistentItem = 'Q99999999';
		const response = await newGetItemLabelsRequestBuilder( nonExistentItem ).makeRequest();

		assert.strictEqual( response.status, 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'item-not-found' );
		assert.include( response.body.message, nonExistentItem );
	} );
} );
