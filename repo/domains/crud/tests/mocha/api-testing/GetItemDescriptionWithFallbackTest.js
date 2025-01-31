'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { getLatestEditMetadata, createRedirectForItem } = require( '../helpers/entityHelper' );
const {
	newGetItemDescriptionWithFallbackRequestBuilder,
	newCreateItemRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newGetItemDescriptionWithFallbackRequestBuilder().getRouteDescription(), () => {
	let itemId;
	const languageWithDescription = 'de';
	const itemDeDescription = `item-description-${utils.uniq()}`;

	before( async () => {
		const createItemResponse = await newCreateItemRequestBuilder( {
			descriptions: { [ languageWithDescription ]: itemDeDescription }
		} ).assertValidRequest().makeRequest();
		expect( createItemResponse ).to.have.status( 201 );
		itemId = createItemResponse.body.id;
	} );

	it( '200 - can get a language specific description of an item', async () => {
		const testItemCreationMetadata = await getLatestEditMetadata( itemId );

		const response = await newGetItemDescriptionWithFallbackRequestBuilder( itemId, languageWithDescription )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual( response.body, itemDeDescription );
		assert.strictEqual( response.header.etag, `"${testItemCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testItemCreationMetadata.timestamp );
	} );

	it( '307 - language fallback redirect', async () => {
		const response = await newGetItemDescriptionWithFallbackRequestBuilder( itemId, 'bar' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 307 );
		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `entities/items/${itemId}/descriptions/${languageWithDescription}` )
		);
	} );

	it( '308 - item redirected', async () => {
		const redirectTarget = itemId;
		const redirectSource = await createRedirectForItem( redirectTarget );
		const response = await newGetItemDescriptionWithFallbackRequestBuilder( redirectSource, 'en' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 308 );
		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `entities/items/${redirectTarget}/descriptions_with_language_fallback/en` )
		);
	} );

	it( '400 - bad request, invalid item ID', async () => {
		const invalidItemId = 'X123';
		const response = await newGetItemDescriptionWithFallbackRequestBuilder( invalidItemId, languageWithDescription )
			.assertInvalidRequest()
			.makeRequest();

		assertValidError( response, 400, 'invalid-path-parameter', { parameter: 'item_id' } );
	} );

	it( '400 - bad request, invalid language code', async () => {
		const response = await newGetItemDescriptionWithFallbackRequestBuilder( 'Q123', '1e' )
			.assertInvalidRequest()
			.makeRequest();

		assertValidError( response, 400, 'invalid-path-parameter', { parameter: 'language_code' } );
	} );

	it( '404 - item does not exist', async () => {
		const nonExistentItem = 'Q99999999';
		const response = await newGetItemDescriptionWithFallbackRequestBuilder( nonExistentItem, languageWithDescription )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'item' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	it( '404 - item has no description in the requested language, or any language in its fallback chain', async () => {
		const languageCode = 'ko';
		const response = await newGetItemDescriptionWithFallbackRequestBuilder( itemId, languageCode )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'description' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );
} );
