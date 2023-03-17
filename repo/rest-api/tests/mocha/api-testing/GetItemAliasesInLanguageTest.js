'use strict';

const { createEntity, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { newGetItemAliasesInLanguageRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { assert } = require( 'api-testing' );

describe( 'GET /entities/items/{id}/aliases/{language_code}', () => {
	let itemId;

	before( async () => {
		const createItemResponse = await createEntity( 'item', {
			aliases: {
				en: [
					{
						language: 'en',
						value: 'Douglas Noël Adams'
					},
					{
						language: 'en',
						value: 'DNA'
					}
				]
			}
		} );

		itemId = createItemResponse.entity.id;
	} );

	it( 'can get language specific aliases of an item', async () => {
		const testItemCreationMetadata = await getLatestEditMetadata( itemId );

		const response = await newGetItemAliasesInLanguageRequestBuilder( itemId, 'en' )
			.assertValidRequest()
			.makeRequest();

		assert.strictEqual( response.status, 200 );
		assert.deepEqual( response.body, [ 'Douglas Noël Adams', 'DNA' ] );
		assert.strictEqual( response.header.etag, `"${testItemCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testItemCreationMetadata.timestamp );
	} );

} );
