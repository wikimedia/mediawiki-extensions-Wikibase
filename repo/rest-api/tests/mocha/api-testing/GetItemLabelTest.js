'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity, getLatestEditMetadata, createRedirectForItem } = require( '../helpers/entityHelper' );
const { newGetItemLabelRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

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

		expect( response ).to.have.status( 200 );
		assert.deepEqual( response.body, 'potato' );
		assert.strictEqual( response.header.etag, `"${testItemCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testItemCreationMetadata.timestamp );
	} );

	it( 'responds 404 in case the item does not exist', async () => {
		const nonExistentItem = 'Q99999999';
		const response = await newGetItemLabelRequestBuilder( nonExistentItem, 'en' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'item-not-found' );
		assert.include( response.body.message, nonExistentItem );
	} );

	it( 'responds 404 in case the item has no label in the requested language', async () => {
		const languageCode = 'ko';
		const response = await newGetItemLabelRequestBuilder( itemId, languageCode )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'label-not-defined' );
		assert.include( response.body.message, languageCode );
	} );

	it( '308 - item redirected', async () => {
		const redirectTarget = itemId;
		const redirectSource = await createRedirectForItem( redirectTarget );

		const response = await newGetItemLabelRequestBuilder( redirectSource, 'en' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 308 );

		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `rest.php/wikibase/v0/entities/items/${redirectTarget}/labels/en` )
		);
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const invalidItemId = 'X123';
		const response = await newGetItemLabelRequestBuilder( invalidItemId, 'en' )
			.assertInvalidRequest()
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'invalid-item-id' );
		assert.include( response.body.message, invalidItemId );
	} );

	it( '400 error - bad request, invalid language code', async () => {
		const invalidLanguageCode = '1e';
		const response = await newGetItemLabelRequestBuilder( 'Q123', invalidLanguageCode )
			.assertInvalidRequest()
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'invalid-language-code' );
		assert.include( response.body.message, invalidLanguageCode );
	} );
} );
