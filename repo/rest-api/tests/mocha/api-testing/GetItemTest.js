'use strict';

const { createEntity, createRedirectForItem, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { assert, utils } = require( 'api-testing' );
const { newGetItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

function makeEtag( ...revisionIds ) {
	return revisionIds.map( ( revId ) => `"${revId}"` ).join( ',' );
}

const germanLabel = 'a-German-label-' + utils.uniq();
const englishLabel = 'an-English-label-' + utils.uniq();
const englishDescription = 'an-English-description-' + utils.uniq();

describe( 'GET /entities/items/{id}', () => {
	let testItemId;
	let testModified;
	let testRevisionId;

	function newValidRequestBuilderWithTestItem() {
		return newGetItemRequestBuilder( testItemId ).assertValidRequest();
	}

	function assertValid200Response( response ) {
		assert.equal( response.status, 200 );
		assert.equal( response.body.id, testItemId );
		assert.deepEqual( response.body.aliases, {} ); // expect {}, not []
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, makeEtag( testRevisionId ) );
	}

	before( async () => {
		const createItemResponse = await createEntity( 'item', {
			labels: {
				de: { language: 'de', value: germanLabel },
				en: { language: 'en', value: englishLabel }
			},
			descriptions: {
				en: { language: 'en', value: englishDescription }
			}
		} );
		testItemId = createItemResponse.entity.id;

		const testItemCreationMetadata = await getLatestEditMetadata( testItemId );
		testModified = testItemCreationMetadata.timestamp;
		testRevisionId = testItemCreationMetadata.revid;
	} );

	it( 'can GET an item with metadata', async () => {
		const response = await newValidRequestBuilderWithTestItem().makeRequest();

		assertValid200Response( response );
	} );

	it( 'can GET a partial item with single _fields param', async () => {
		const response = await newValidRequestBuilderWithTestItem()
			.withQueryParam( '_fields', 'labels' )
			.makeRequest();

		assert.equal( response.status, 200 );
		assert.deepEqual( response.body, {
			id: testItemId,
			labels: {
				de: germanLabel,
				en: englishLabel
			}
		} );
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, makeEtag( testRevisionId ) );
	} );

	it( 'can GET a partial item with multiple _fields params', async () => {
		const response = await newValidRequestBuilderWithTestItem()
			.withQueryParam( '_fields', 'labels,descriptions,aliases' )
			.makeRequest();

		assert.equal( response.status, 200 );
		assert.deepEqual( response.body, {
			id: testItemId,
			labels: {
				de: germanLabel,
				en: englishLabel
			},
			descriptions: {
				en: englishDescription
			},
			aliases: {} // expect {}, not []
		} );
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, makeEtag( testRevisionId ) );
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const itemId = 'X123';
		const response = await newGetItemRequestBuilder( itemId )
			.makeRequest();

		assert.equal( response.status, 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'invalid-item-id' );
		assert.include( response.body.message, itemId );
	} );

	it( '400 error - bad request, invalid field', async () => {
		const itemId = 'Q123';
		const response = await newGetItemRequestBuilder( itemId )
			.withQueryParam( '_fields', 'unknown_field' )
			.assertInvalidRequest()
			.makeRequest();

		assert.equal( response.status, 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'invalid-field' );
		assert.include( response.body.message, 'unknown_field' );
	} );

	it( '404 error - item not found', async () => {
		const itemId = 'Q999999';
		const response = await newGetItemRequestBuilder( itemId ).makeRequest();

		assert.equal( response.status, 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'item-not-found' );
		assert.include( response.body.message, itemId );
	} );

	describe( 'redirects', () => {
		let redirectSourceId;

		before( async () => {
			redirectSourceId = await createRedirectForItem( testItemId );
		} );

		it( 'responds with a 308 including the redirect target location', async () => {
			const response = await newGetItemRequestBuilder( redirectSourceId ).makeRequest();

			assert.equal( response.status, 308 );

			const redirectLocation = new URL( response.headers.location );
			assert.isTrue( redirectLocation.pathname.endsWith( `rest.php/wikibase/v0/entities/items/${testItemId}` ) );
			assert.empty( redirectLocation.search );
		} );

		it( 'keeps the original fields param in the Location header', async () => {
			const fields = 'labels,statements';
			const response = await newGetItemRequestBuilder( redirectSourceId )
				.withQueryParam( '_fields', fields )
				.makeRequest();

			assert.equal( response.status, 308 );

			const redirectLocation = new URL( response.headers.location );
			assert.equal(
				redirectLocation.searchParams.get( '_fields' ),
				fields
			);
		} );

	} );

} );
