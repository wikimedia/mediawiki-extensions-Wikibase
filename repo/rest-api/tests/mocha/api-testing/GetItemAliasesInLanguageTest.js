'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity, getLatestEditMetadata, createRedirectForItem } = require( '../helpers/entityHelper' );
const { newGetItemAliasesInLanguageRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

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

		expect( response ).to.have.status( 200 );
		assert.deepEqual( response.body, [ 'Douglas Noël Adams', 'DNA' ] );
		assert.strictEqual( response.header.etag, `"${testItemCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testItemCreationMetadata.timestamp );
	} );

	it( '308 - item redirected', async () => {
		const redirectTarget = itemId;
		const redirectSource = await createRedirectForItem( redirectTarget );

		const response = await newGetItemAliasesInLanguageRequestBuilder( redirectSource, 'en' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 308 );

		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `rest.php/wikibase/v0/entities/items/${redirectTarget}/aliases/en` )
		);
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const invalidItemId = 'X123';
		const response = await newGetItemAliasesInLanguageRequestBuilder( invalidItemId, 'en' )
			.assertInvalidRequest()
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'invalid-item-id' );
		assert.include( response.body.message, invalidItemId );
	} );

	it( '400 error - bad request, invalid language code', async () => {
		const invalidLanguageCode = '1e';
		const response = await newGetItemAliasesInLanguageRequestBuilder( 'Q123', invalidLanguageCode )
			.assertInvalidRequest()
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'invalid-language-code' );
		assert.include( response.body.message, invalidLanguageCode );
	} );

	it( 'responds 404 in case the item does not exist', async () => {
		const nonExistentItem = 'Q99999999';
		const response = await newGetItemAliasesInLanguageRequestBuilder( nonExistentItem, 'en' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'item-not-found' );
		assert.include( response.body.message, nonExistentItem );
	} );

	it( 'responds 404 in case the item has no aliases in the requested language', async () => {
		const languageCode = 'de';
		const response = await newGetItemAliasesInLanguageRequestBuilder( itemId, languageCode )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'aliases-not-defined' );
		assert.include( response.body.message, languageCode );
	} );
} );
