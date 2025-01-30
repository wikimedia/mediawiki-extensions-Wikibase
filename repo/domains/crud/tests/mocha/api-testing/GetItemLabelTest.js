'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { getLatestEditMetadata, createRedirectForItem } = require( '../helpers/entityHelper' );
const { newGetItemLabelRequestBuilder, newCreateItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newGetItemLabelRequestBuilder().getRouteDescription(), () => {
	let itemId;

	before( async () => {
		const createItemResponse = await newCreateItemRequestBuilder( { labels: { en: 'potato' } } ).makeRequest();
		itemId = createItemResponse.body.id;
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

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'item' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	it( 'responds 404 in case the item has no label in the requested language', async () => {
		const languageCode = 'ko';
		const response = await newGetItemLabelRequestBuilder( itemId, languageCode )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'label' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
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
				.endsWith( `rest.php/wikibase/v1/entities/items/${redirectTarget}/labels/en` )
		);
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const invalidItemId = 'X123';
		const response = await newGetItemLabelRequestBuilder( invalidItemId, 'en' )
			.assertInvalidRequest()
			.makeRequest();

		assertValidError(
			response,
			400,
			'invalid-path-parameter',
			{ parameter: 'item_id' }
		);
	} );

	it( '400 error - bad request, invalid language code', async () => {
		const response = await newGetItemLabelRequestBuilder( 'Q123', '1e' )
			.assertInvalidRequest()
			.makeRequest();

		assertValidError(
			response,
			400,
			'invalid-path-parameter',
			{ parameter: 'language_code' }
		);
	} );
} );
