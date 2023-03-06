'use strict';

const { createEntity, getLatestEditMetadata, createRedirectForItem } = require( '../helpers/entityHelper' );
const { newGetItemAliasesRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { assert } = require( 'api-testing' );

describe( 'GET /entities/items/{id}/aliases', () => {
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

	it( 'can get the aliases of an item', async () => {
		const testItemCreationMetadata = await getLatestEditMetadata( itemId );

		const response = await newGetItemAliasesRequestBuilder( itemId )
			.assertValidRequest()
			.makeRequest();

		assert.strictEqual( response.status, 200 );
		assert.deepEqual( response.body, { en: [ 'Douglas Noël Adams', 'DNA' ] } );
		assert.strictEqual( response.header.etag, `"${testItemCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testItemCreationMetadata.timestamp );
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const invalidItemId = 'X123';
		const response = await newGetItemAliasesRequestBuilder( invalidItemId )
			.assertInvalidRequest()
			.makeRequest();

		assert.strictEqual( response.status, 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'invalid-item-id' );
		assert.include( response.body.message, invalidItemId );
	} );

	it( 'responds 404 in case the item does not exist', async () => {
		const nonExistentItemId = 'Q99999999';
		const response = await newGetItemAliasesRequestBuilder( nonExistentItemId )
			.makeRequest();

		assert.strictEqual( response.status, 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'item-not-found' );
		assert.include( response.body.message, nonExistentItemId );
	} );

	it( '308 - item redirected', async () => {
		const redirectTarget = itemId;
		const redirectSource = await createRedirectForItem( redirectTarget );

		const response = await newGetItemAliasesRequestBuilder( redirectSource )
			.assertValidRequest()
			.makeRequest();

		assert.equal( response.status, 308 );

		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `rest.php/wikibase/v0/entities/items/${redirectTarget}/aliases` )
		);
	} );
} );
