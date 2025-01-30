'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { getLatestEditMetadata, createRedirectForItem } = require( '../helpers/entityHelper' );
const {
	newGetItemDescriptionRequestBuilder,
	newCreateItemRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newGetItemDescriptionRequestBuilder().getRouteDescription(), () => {
	let itemId;

	before( async () => {
		const createItemResponse = await newCreateItemRequestBuilder(
			{ descriptions: { en: 'English science fiction writer and humourist' } }
		).makeRequest();
		itemId = createItemResponse.body.id;
	} );

	it( 'can get a language specific description of an item', async () => {
		const testItemCreationMetadata = await getLatestEditMetadata( itemId );

		const response = await newGetItemDescriptionRequestBuilder( itemId, 'en' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual( response.body, 'English science fiction writer and humourist' );
		assert.strictEqual( response.header.etag, `"${testItemCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testItemCreationMetadata.timestamp );
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const invalidItemId = 'X123';
		const response = await newGetItemDescriptionRequestBuilder( invalidItemId, 'en' )
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
		const response = await newGetItemDescriptionRequestBuilder( 'Q123', '1e' )
			.assertInvalidRequest()
			.makeRequest();

		assertValidError(
			response,
			400,
			'invalid-path-parameter',
			{ parameter: 'language_code' }
		);
	} );

	it( 'responds 404 in case the item does not exist', async () => {
		const nonExistentItem = 'Q99999999';
		const response = await newGetItemDescriptionRequestBuilder( nonExistentItem, 'en' )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'item' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	it( 'responds 404 in case the item has no description in the requested language', async () => {
		const languageCode = 'ko';
		const response = await newGetItemDescriptionRequestBuilder( itemId, languageCode )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'description' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	it( '308 - item redirected', async () => {
		const redirectTarget = itemId;
		const redirectSource = await createRedirectForItem( redirectTarget );
		const response = await newGetItemDescriptionRequestBuilder( redirectSource, 'en' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 308 );
		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `rest.php/wikibase/v1/entities/items/${redirectTarget}/descriptions/en` )
		);
	} );
} );
