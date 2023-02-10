'use strict';

const { createEntity, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { newGetItemDescriptionsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { assert } = require( 'api-testing' );

describe( 'GET /entities/items/{id}/descriptions', () => {
	it( 'can get the descriptions of an item', async () => {

		const createItemResponse = await createEntity( 'item', {
			descriptions: {
				en: {
					language: 'en',
					value: 'English science fiction writer and humorist'
				}
			}
		} );

		const itemId = createItemResponse.entity.id;
		const testItemCreationMetadata = await getLatestEditMetadata( itemId );

		const response = await newGetItemDescriptionsRequestBuilder( itemId ).makeRequest();

		assert.strictEqual( response.status, 200 );
		assert.deepEqual( response.body, { en: 'English science fiction writer and humorist' } );
		assert.strictEqual( response.header.etag, `"${testItemCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testItemCreationMetadata.timestamp );
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const itemId = 'X123';
		const response = await newGetItemDescriptionsRequestBuilder( itemId ).assertInvalidRequest().makeRequest();

		assert.strictEqual( response.status, 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'invalid-item-id' );
		assert.include( response.body.message, itemId );
	} );
} );
