'use strict';

const { createEntity, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { newGetItemAliasesRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { assert } = require( 'api-testing' );

describe( 'GET /entities/items/{id}/aliases', () => {
	it( 'can get the aliases of an item', async () => {

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

		const itemId = createItemResponse.entity.id;
		const testItemCreationMetadata = await getLatestEditMetadata( itemId );

		const response = await newGetItemAliasesRequestBuilder( itemId ).makeRequest();

		assert.strictEqual( response.status, 200 );
		assert.deepEqual( response.body, { en: [ 'Douglas Noël Adams', 'DNA' ] } );
		assert.strictEqual( response.header.etag, `"${testItemCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testItemCreationMetadata.timestamp );
	} );
} );
