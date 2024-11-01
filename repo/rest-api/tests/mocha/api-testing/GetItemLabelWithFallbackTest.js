'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { getLatestEditMetadata, createRedirectForItem } = require( '../helpers/entityHelper' );
const {
	newCreateItemRequestBuilder,
	newGetItemLabelWithFallbackRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newGetItemLabelWithFallbackRequestBuilder().getRouteDescription(), () => {
	let itemId;
	const itemArLabel = `بطاطا-${utils.uniq()}`;
	const fallbackLanguageWithExistingLabel = 'ar';

	async function makeRequestWithMulHeader( requestBuilder ) {
		return requestBuilder.withConfigOverride( 'wgWBRepoSettings', { tmpEnableMulLanguageCode: true } )
			.assertValidRequest()
			.makeRequest();
	}

	before( async () => {
		itemId = ( await makeRequestWithMulHeader( newCreateItemRequestBuilder(
			{ labels: { [ fallbackLanguageWithExistingLabel ]: itemArLabel, mul: `mul-label-${utils.uniq()}` } }
		) ) ).body.id;
	} );

	it( '200 - can get a language specific label of an item', async () => {
		const testItemCreationMetadata = await getLatestEditMetadata( itemId );

		const response = await newGetItemLabelWithFallbackRequestBuilder( itemId, fallbackLanguageWithExistingLabel )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual( response.body, itemArLabel );
		assert.strictEqual( response.header.etag, `"${testItemCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testItemCreationMetadata.timestamp );
	} );

	it( '307 - language fallback redirect', async () => {
		const response = await makeRequestWithMulHeader( newGetItemLabelWithFallbackRequestBuilder( itemId, 'arz' ) );

		expect( response ).to.have.status( 307 );
		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `rest.php/wikibase/v1/entities/items/${itemId}/labels/ar` )
		);
	} );

	it( '307 - language fallback redirect mul', async () => {
		const response = await makeRequestWithMulHeader( newGetItemLabelWithFallbackRequestBuilder( itemId, 'en' ) );

		expect( response ).to.have.status( 307 );

		assert.isTrue( new URL( response.headers.location ).pathname.endsWith(
			`rest.php/wikibase/v1/entities/items/${itemId}/labels/mul`
		) );
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
				.endsWith( `rest.php/wikibase/v1/entities/items/${redirectTarget}/labels_with_language_fallback/en` )
		);
	} );

	it( '400 - bad request, invalid item ID', async () => {
		const invalidItemId = 'X123';
		const response = await newGetItemLabelWithFallbackRequestBuilder( invalidItemId, fallbackLanguageWithExistingLabel )
			.assertInvalidRequest()
			.makeRequest();

		assertValidError(
			response,
			400,
			'invalid-path-parameter',
			{ parameter: 'item_id' }
		);
	} );

	it( '400 - bad request, invalid language code', async () => {
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

	it( '404 - in case the item does not exist', async () => {
		const nonExistentItem = 'Q99999999';
		const response = await newGetItemLabelWithFallbackRequestBuilder( nonExistentItem, fallbackLanguageWithExistingLabel )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'item' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	it( '404 - in case the item has no label in the requested or any fallback languages', async () => {
		const itemWithoutMulFallbackId = ( await makeRequestWithMulHeader( newCreateItemRequestBuilder(
			{ labels: { [ fallbackLanguageWithExistingLabel ]: itemArLabel } } ) ) ).body.id;

		const response = await makeRequestWithMulHeader(
			newGetItemLabelWithFallbackRequestBuilder( itemWithoutMulFallbackId, 'en' )
		);

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'label' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );
} );
