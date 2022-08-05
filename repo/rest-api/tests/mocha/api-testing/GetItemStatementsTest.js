'use strict';

const { assert } = require( 'api-testing' );
const {
	createEntity,
	createSingleItem,
	createRedirectForItem,
	getLatestEditMetadata
} = require( '../helpers/entityHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );

function newGetItemStatementsRequestBuilder( itemId ) {
	return new RequestBuilder()
		.withRoute( 'GET', '/entities/items/{item_id}/statements' )
		.withPathParam( 'item_id', itemId );
}

describe( 'GET /entities/items/{id}/statements', () => {

	function makeEtag( ...revisionIds ) {
		return revisionIds.map( ( revId ) => `"${revId}"` ).join( ',' );
	}

	let testItemId;
	let testPropertyId;
	let testModified;
	let testRevisionId;

	before( async () => {
		const createSingleItemResponse = await createSingleItem();

		testItemId = createSingleItemResponse.entity.id;
		testPropertyId = Object.keys( createSingleItemResponse.entity.claims )[ 0 ];

		const testItemCreationMetadata = await getLatestEditMetadata( testItemId );
		testModified = testItemCreationMetadata.timestamp;
		testRevisionId = testItemCreationMetadata.revid;
	} );

	it( 'can GET statements of an item with metadata', async () => {
		const response = await newGetItemStatementsRequestBuilder( testItemId )
			.assertValidRequest()
			.makeRequest();

		assert.equal( response.status, 200 );
		assert.exists( response.body[ testPropertyId ] );
		assert.equal( response.body[ testPropertyId ][ 0 ].mainsnak.snaktype, 'value' );
		assert.equal( response.body[ testPropertyId ][ 1 ].mainsnak.snaktype, 'novalue' );
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, makeEtag( testRevisionId ) );
	} );

	it( 'can GET empty statements list', async () => {
		const createItemResponse = await createEntity( 'item',
			{ labels: { en: { language: 'en', value: 'item without statements' } } }
		);
		const response = await newGetItemStatementsRequestBuilder( createItemResponse.entity.id )
			.assertValidRequest()
			.makeRequest();

		assert.equal( response.status, 200 );
		assert.empty( response.body );
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const itemId = 'X123';
		const response = await newGetItemStatementsRequestBuilder( itemId )
			.assertInvalidRequest()
			.makeRequest();

		assert.equal( response.status, 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'invalid-item-id' );
		assert.include( response.body.message, itemId );
	} );

	it( '404 error - item not found', async () => {
		const itemId = 'Q999999';
		const response = await newGetItemStatementsRequestBuilder( itemId )
			.assertValidRequest()
			.makeRequest();

		assert.equal( response.status, 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'item-not-found' );
		assert.include( response.body.message, itemId );
	} );

	it( '308 - item redirected', async () => {
		const redirectTarget = testItemId;
		const redirectSource = await createRedirectForItem( redirectTarget );

		const response = await newGetItemStatementsRequestBuilder( redirectSource )
			.assertValidRequest()
			.makeRequest();

		assert.equal( response.status, 308 );

		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `rest.php/wikibase/v0/entities/items/${redirectTarget}/statements` )
		);
	} );

} );
