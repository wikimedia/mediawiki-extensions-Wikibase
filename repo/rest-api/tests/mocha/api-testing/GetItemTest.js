'use strict';

const { requireExtensions } = require( '../../../../../tests/api-testing/utils' );
const { createEntity, createRedirectForItem } = require( '../helpers/entityHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const { action, assert, clientFactory, utils } = require( 'api-testing' );

function newGetItemRequestBuilder( itemId ) {
	return new RequestBuilder()
		.withRoute( '/entities/items/{entity_id}' )
		.withPathParam( 'entity_id', itemId );
}

function makeEtag( ...revisionIds ) {
	return revisionIds.map( ( revId ) => `"${revId}"` ).join( ',' );
}

const basePath = 'rest.php/wikibase/v0';
const germanLabel = 'a-German-label-' + utils.uniq();
const englishLabel = 'an-English-label-' + utils.uniq();
const englishDescription = 'an-English-description-' + utils.uniq();

describe( 'GET /entities/items/{id} ', () => {
	let testItemId;
	let testModified;
	let testRevisionId;

	function newValidRequestBuilderWithTestItem() {
		return newGetItemRequestBuilder( testItemId ).assertValidRequest();
	}
	function newInvalidRequestBuilderWithTestItem() {
		return newGetItemRequestBuilder( testItemId ).assertInvalidRequest();
	}

	function assertValid200Response( response ) {
		assert.equal( response.status, 200 );
		assert.equal( response.body.id, testItemId );
		assert.deepEqual( response.body.aliases, {} ); // expect {}, not []
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, makeEtag( testRevisionId ) );
	}

	function assertValid304Response( response ) {
		assert.equal( response.status, 304 );
		assert.equal( response.header.etag, makeEtag( testRevisionId ) );
		assert.equal( response.text, '' );
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

		const getItemMetadata = await action.getAnon().action( 'wbgetentities', {
			ids: testItemId
		} );

		testModified = new Date( getItemMetadata.entities[ testItemId ].modified ).toUTCString();
		testRevisionId = getItemMetadata.entities[ testItemId ].lastrevid;
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

	describe( 'If-None-Match', () => {
		describe( '200 response', () => {
			it( 'if the current item revision is newer than the ID provided', async () => {
				const response = await newValidRequestBuilderWithTestItem()
					.withHeader( 'If-None-Match', makeEtag( testRevisionId - 1 ) )
					.makeRequest();

				assertValid200Response( response );
			} );

			it( 'if the current revision is newer than any of the IDs provided', async () => {
				const response = await newValidRequestBuilderWithTestItem()
					.withHeader( 'If-None-Match', makeEtag( testRevisionId - 1, testRevisionId - 2 ) )
					.makeRequest();
				assertValid200Response( response );
			} );

			it( 'if the provided ETag is not a valid revision ID', async () => {
				const response = await newValidRequestBuilderWithTestItem()
					.withHeader( 'If-None-Match', '"foo"' )
					.makeRequest();
				assertValid200Response( response );
			} );

			it( 'if all the provided ETags are not valid revision IDs', async () => {
				const response = await newValidRequestBuilderWithTestItem()
					.withHeader( 'If-None-Match', makeEtag( 'foo', 'bar' ) )
					.makeRequest();
				assertValid200Response( response );
			} );

			it( 'if the current revision is newer than any IDs provided (while others are invalid IDs)',
				async () => {
					const response = await newValidRequestBuilderWithTestItem()
						.withHeader( 'If-None-Match', makeEtag( 'foo', testRevisionId - 1, 'bar' ) )
						.makeRequest();
					assertValid200Response( response );
				}
			);

			it( 'if the header is invalid', async () => {
				const response = await newInvalidRequestBuilderWithTestItem()
					.withHeader( 'If-None-Match', 'not in spec for a If-None-Match header - 200 response' )
					.makeRequest();
				assertValid200Response( response );
			} );

			it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
				const response = await newValidRequestBuilderWithTestItem()
					.withHeader( 'If-Modified-Since', testModified ) // this header on its own would return 304
					.withHeader( 'If-None-Match', makeEtag( testRevisionId - 1 ) )
					.makeRequest();
				assertValid200Response( response );
			} );

		} );

		describe( '304 response', () => {
			it( 'if the current revision ID is the same as the one provided', async () => {
				const response = await newValidRequestBuilderWithTestItem()
					.withHeader( 'If-None-Match', makeEtag( testRevisionId ) )
					.makeRequest();
				assertValid304Response( response );
			} );

			it( 'if the current revision ID is the same as one of the IDs provided', async () => {
				const response = await newValidRequestBuilderWithTestItem()
					.withHeader( 'If-None-Match', makeEtag( testRevisionId - 1, testRevisionId ) )
					.makeRequest();
				assertValid304Response( response );
			} );

			it( 'if the current revision ID is the same as one of the IDs provided (while others are invalid IDs)',
				async () => {
					const response = await newValidRequestBuilderWithTestItem()
						.withHeader( 'If-None-Match', makeEtag( 'foo', testRevisionId ) )
						.makeRequest();
					assertValid304Response( response );
				}
			);

			it( 'if the header is *', async () => {
				const response = await newValidRequestBuilderWithTestItem()
					.withHeader( 'If-None-Match', '*' )
					.makeRequest();
				assertValid304Response( response );
			} );

			it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
				const response = await newValidRequestBuilderWithTestItem()
					// this header on its own would return 200
					.withHeader( 'If-Modified-Since', 'Fri, 1 Apr 2022 12:00:00 GMT' )
					.withHeader( 'If-None-Match', makeEtag( testRevisionId ) )
					.makeRequest();
				assertValid304Response( response );
			} );
		} );
	} );

	describe( 'If-Modified-Since', () => {
		describe( '200 response', () => {
			it( 'If-Modified-Since header is older than current revision', async () => {
				const response = await newValidRequestBuilderWithTestItem()
					.withHeader( 'If-Modified-Since', 'Fri, 1 Apr 2022 12:00:00 GMT' )
					.makeRequest();
				assertValid200Response( response );
			} );
		} );

		describe( '304 response', () => {
			it( 'If-Modified-Since header is same as current revision', async () => {
				const response = await newValidRequestBuilderWithTestItem()
					.withHeader( 'If-Modified-Since', `${testModified}` )
					.makeRequest();
				assertValid304Response( response );
			} );

			it( 'If-Modified-Since header is after current revision', async () => {
				const futureDate = new Date(
					new Date( testModified ).getTime() + 5000
				).toUTCString();
				const response = await newValidRequestBuilderWithTestItem()
					.withHeader( 'If-Modified-Since', futureDate )
					.makeRequest();

				assertValid304Response( response );
			} );
		} );
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

	describe( 'authentication', () => {

		it( 'has an X-Authenticated-User header with the logged in user', async () => {
			const mindy = await action.mindy();

			const response = await clientFactory.getRESTClient( basePath, mindy )
				.get( `/entities/items/${testItemId}` );

			assertValid200Response( response );
			assert.header( response, 'X-Authenticated-User', mindy.username );
		} );

		describe.skip( 'OAuth', () => { // Skipping due to apache auth header issues. See T305709
			before( requireExtensions( [ 'OAuth' ] ) );

			it( 'responds with an error given an invalid bearer token', async () => {
				const response = await newGetItemRequestBuilder( testItemId )
					.withHeader( 'Authorization', 'Bearer this-is-an-invalid-token' )
					.makeRequest();

				assert.equal( response.status, 403 );
			} );

		} );

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
			assert.isTrue( redirectLocation.pathname.endsWith( `${basePath}/entities/items/${testItemId}` ) );
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
