'use strict';

const { createEntity, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { newGetItemLabelRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { assert } = require( 'api-testing' );

describe( 'GET /entities/items/{id}/labels/{language_code}', () => {
	let itemId;

	before( async () => {
		const createItemResponse = await createEntity( 'item', {
			labels: {
				en: { language: 'en', value: 'potato' }
			}
		} );

		itemId = createItemResponse.entity.id;
	} );

	it( 'can get a language specific label of an item', async () => {
		const testItemCreationMetadata = await getLatestEditMetadata( itemId );

		const response = await newGetItemLabelRequestBuilder( itemId, 'en' )
			.assertValidRequest()
			.makeRequest();

		assert.strictEqual( response.status, 200 );
		assert.deepEqual( response.body, 'potato' );
		assert.strictEqual( response.header.etag, `"${testItemCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testItemCreationMetadata.timestamp );
	} );

} );
