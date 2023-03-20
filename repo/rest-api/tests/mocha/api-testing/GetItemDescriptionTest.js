'use strict';

const { createEntity, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { newGetItemDescriptionRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { assert } = require( 'api-testing' );

describe( 'GET /entities/items/{id}/descriptions/{language_code}', () => {
	let itemId;

	before( async () => {
		const createItemResponse = await createEntity( 'item', {
			descriptions: {
				en: {
					language: 'en',
					value: 'English science fiction writer and humourist'
				}
			}
		} );

		itemId = createItemResponse.entity.id;
	} );

	it( 'can get a language specific description of an item', async () => {
		const testItemCreationMetadata = await getLatestEditMetadata( itemId );

		const response = await newGetItemDescriptionRequestBuilder( itemId, 'en' )
			.assertValidRequest()
			.makeRequest();

		assert.strictEqual( response.status, 200 );
		assert.deepEqual( response.body, 'English science fiction writer and humourist' );
		assert.strictEqual( response.header.etag, `"${testItemCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testItemCreationMetadata.timestamp );
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const invalidItemId = 'X123';
		const response = await newGetItemDescriptionRequestBuilder( invalidItemId, 'en' )
			.assertInvalidRequest()
			.makeRequest();

		assert.strictEqual( response.status, 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'invalid-item-id' );
		assert.include( response.body.message, invalidItemId );
	} );

	it( '400 error - bad request, invalid language code', async () => {
		const invalidLanguageCode = '1e';
		const response = await newGetItemDescriptionRequestBuilder( 'Q123', invalidLanguageCode )
			.assertInvalidRequest()
			.makeRequest();

		assert.strictEqual( response.status, 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'invalid-language-code' );
		assert.include( response.body.message, invalidLanguageCode );
	} );

} );
