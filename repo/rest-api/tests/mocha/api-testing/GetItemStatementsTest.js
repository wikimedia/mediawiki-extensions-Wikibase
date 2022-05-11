'use strict';

const { REST, action, assert, clientFactory } = require( 'api-testing' );
const { createEntity, createSingleItem, createRedirectForItem } = require( '../helpers/entityHelper' );
const { requireExtensions } = require( '../../../../../tests/api-testing/utils' );

const basePath = 'rest.php/wikibase/v0';
const rest = new REST( basePath );

describe( 'GET /entities/items/{id}/statements ', () => {

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

		const getItemMetadata = await action.getAnon().action( 'wbgetentities', {
			ids: testItemId
		} );

		testModified = new Date( getItemMetadata.entities[ testItemId ].modified ).toUTCString();
		testRevisionId = getItemMetadata.entities[ testItemId ].lastrevid;
	} );

	it( 'can GET statements of an item with metadata', async () => {
		const response = await rest.get( `/entities/items/${testItemId}/statements` );

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
		testItemId = createItemResponse.entity.id;
		const response = await rest.get( `/entities/items/${testItemId}/statements` );

		assert.equal( response.status, 200 );
		assert.empty( response.body );
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const itemId = 'X123';
		const response = await rest.get( `/entities/items/${itemId}/statements` );

		assert.equal( response.status, 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'invalid-item-id' );
		assert.include( response.body.message, itemId );
	} );

	it( '404 error - item not found', async () => {
		const itemId = 'Q999999';
		const response = await rest.get( `/entities/items/${itemId}/statements` );

		assert.equal( response.status, 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'item-not-found' );
		assert.include( response.body.message, itemId );
	} );

	it( '308 - item redirected', async () => {
		const redirectTarget = testItemId;
		const redirectSource = await createRedirectForItem( redirectTarget );

		const response = await rest.get( `/entities/items/${redirectSource}/statements` );

		assert.equal( response.status, 308 );

		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `${basePath}/entities/items/${redirectTarget}/statements` )
		);
	} );

	describe( 'authentication', () => {

		it( 'has an X-Authenticated-User header with the logged in user', async () => {
			const mindy = await action.mindy();

			const response = await clientFactory.getRESTClient( basePath, mindy )
				.get( `/entities/items/${testItemId}/statements` );

			assert.equal( response.status, 200 );
			assert.header( response, 'X-Authenticated-User', mindy.username );
		} );

		describe.skip( 'OAuth', () => { // Skipping due to apache auth header issues. See T305709
			before( requireExtensions( [ 'OAuth' ] ) );

			it( 'responds with an error given an invalid bearer token', async () => {
				const response = await rest.get(
					`/entities/items/${testItemId}`,
					{},
					{ Authorization: 'Bearer this-is-an-invalid-token' }
				);

				assert.equal( response.status, 403 );
			} );

		} );

	} );

} );
