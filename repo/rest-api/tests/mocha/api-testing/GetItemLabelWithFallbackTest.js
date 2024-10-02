'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { getLatestEditMetadata, createRedirectForItem } = require( '../helpers/entityHelper' );
const { newCreateItemRequestBuilder, newGetItemLabelWithFallbackRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newGetItemLabelWithFallbackRequestBuilder().getRouteDescription(), () => {
	let itemId;
	const itemLabel = `potato-${utils.uniq()}`;
	const fallbackLanguageWithExistingLabel = 'en';

	before( async () => {
		itemId = ( await newCreateItemRequestBuilder(
			{ labels: { [ fallbackLanguageWithExistingLabel ]: itemLabel } }
		).makeRequest() ).body.id;
	} );

	it( 'can get a language specific label of an item', async () => {
		const testItemCreationMetadata = await getLatestEditMetadata( itemId );

		const response = await newGetItemLabelWithFallbackRequestBuilder( itemId, 'en' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual( response.body, itemLabel );
		assert.strictEqual( response.header.etag, `"${testItemCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testItemCreationMetadata.timestamp );
	} );

	it( 'responds 404 in case the item does not exist', async () => {
		const nonExistentItem = 'Q99999999';
		const response = await newGetItemLabelWithFallbackRequestBuilder( nonExistentItem, 'en' )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'item' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	it( 'responds 404 in case the item has no label in the requested or any fallback languages', async () => {
		const itemIdWithoutFallback =
			( await newCreateItemRequestBuilder( { labels: { de: `kartoffel-${utils.uniq()}` } } ).makeRequest() ).body.id;

		const response = await newGetItemLabelWithFallbackRequestBuilder( itemIdWithoutFallback, 'ko' )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'label' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	it( '307 - language fallback redirect', async () => {
		const response = await newGetItemLabelWithFallbackRequestBuilder( itemId, 'en-ca' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 307 );
		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `rest.php/wikibase/v0/entities/items/${itemId}/labels/${fallbackLanguageWithExistingLabel}` )
		);
	} );

	it( '308 - item redirected', async () => {
		const redirectTarget = itemId;
		const redirectSource = await createRedirectForItem( redirectTarget );

		const response = await newGetItemLabelWithFallbackRequestBuilder( redirectSource, 'en' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 308 );
		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `rest.php/wikibase/v0/entities/items/${redirectTarget}/labels_with_language_fallback/en` )
		);
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const invalidItemId = 'X123';
		const response = await newGetItemLabelWithFallbackRequestBuilder( invalidItemId, 'en' )
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
		const response = await newGetItemLabelWithFallbackRequestBuilder( 'Q123', '1e' )
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
