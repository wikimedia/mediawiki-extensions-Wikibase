'use strict';

const { assert, action } = require( 'api-testing' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const { createSingleItem } = require( '../helpers/entityHelper' );

function makeEtag( ...revisionIds ) {
	return revisionIds.map( ( revId ) => `"${revId}"` ).join( ',' );
}

function assertValid304Response( response, revisionId ) {
	assert.equal( response.status, 304 );
	assert.equal( response.header.etag, makeEtag( revisionId ) );
	assert.equal( response.text, '' );
}

function assertValid200Response( response, revisionId, lastModified ) {
	assert.equal( response.status, 200 );
	assert.equal( response.header[ 'last-modified' ], lastModified );
	assert.equal( response.header.etag, makeEtag( revisionId ) );
}

describe( 'Conditional requests', () => {

	let itemId;
	let statementId;
	let latestRevisionId;
	let lastModifiedDate;

	before( async () => {
		const createSingleItemResponse = await createSingleItem();
		itemId = createSingleItemResponse.entity.id;
		const claims = createSingleItemResponse.entity.claims;
		statementId = Object.values( claims )[ 0 ][ 0 ].id;

		const getItemMetadata = await action.getAnon().action( 'wbgetentities', {
			ids: itemId
		} );
		lastModifiedDate = new Date( getItemMetadata.entities[ itemId ].modified ).toUTCString();
		latestRevisionId = getItemMetadata.entities[ itemId ].lastrevid;
	} );

	[ // eslint-disable-line mocha/no-setup-in-describe
		{
			route: '/entities/items/{item_id}',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( '/entities/items/{item_id}' )
				.withPathParam( 'item_id', itemId )
		},
		{
			route: '/entities/items/{item_id}/statements',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( '/entities/items/{item_id}/statements' )
				.withPathParam( 'item_id', itemId )
		},
		{
			route: '/entities/items/{item_id}/statements/{statement_id}',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( '/entities/items/{item_id}/statements/{statement_id}' )
				.withPathParam( 'item_id', itemId )
				.withPathParam( 'statement_id', statementId )
		},
		{
			route: '/statements/{statement_id}',
			newRequestBuilder: () => new RequestBuilder()
				.withRoute( '/statements/{statement_id}' )
				.withPathParam( 'statement_id', statementId )
		}
	].forEach( ( { route, newRequestBuilder } ) => {
		describe( `If-None-Match - ${route}`, () => {
			describe( '200 response', () => {
				it( 'if the current item revision is newer than the ID provided', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId - 1 ) )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'if the current revision is newer than any of the IDs provided', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId - 1, latestRevisionId - 2 ) )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'if the provided ETag is not a valid revision ID', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', '"foo"' )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'if all the provided ETags are not valid revision IDs', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', makeEtag( 'foo', 'bar' ) )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'if the current revision is newer than any IDs provided (while others are invalid IDs)',
					async () => {
						const response = await newRequestBuilder()
							.withHeader( 'If-None-Match', makeEtag( 'foo', latestRevisionId - 1, 'bar' ) )
							.assertValidRequest()
							.makeRequest();
						assertValid200Response( response, latestRevisionId, lastModifiedDate );
					}
				);

				it( 'if the header is invalid', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', 'not in spec for a If-None-Match header - 200 response' )
						.assertInvalidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-Modified-Since', lastModifiedDate ) // this header on its own would return 304
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId - 1 ) )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

			} );

			describe( '304 response', () => {
				it( 'if the current revision ID is the same as the one provided', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId ) )
						.assertValidRequest()
						.makeRequest();
					assertValid304Response( response, latestRevisionId );
				} );

				it( 'if the current revision ID is the same as one of the IDs provided', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId - 1, latestRevisionId ) )
						.assertValidRequest()
						.makeRequest();
					assertValid304Response( response, latestRevisionId );
				} );

				it( 'if the current revision ID is the same as one of the IDs provided (while others are invalid IDs)',
					async () => {
						const response = await newRequestBuilder()
							.withHeader( 'If-None-Match', makeEtag( 'foo', latestRevisionId ) )
							.assertValidRequest()
							.makeRequest();
						assertValid304Response( response, latestRevisionId );
					}
				);

				it( 'if the header is *', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', '*' )
						.assertValidRequest()
						.makeRequest();
					assertValid304Response( response, latestRevisionId );
				} );

				it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
					const response = await newRequestBuilder()
						// this header on its own would return 200
						.withHeader( 'If-Modified-Since', 'Fri, 1 Apr 2022 12:00:00 GMT' )
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId ) )
						.assertValidRequest()
						.makeRequest();
					assertValid304Response( response, latestRevisionId );
				} );
			} );
		} );

		describe( `If-Modified-Since - ${route}`, () => {
			describe( '200 response', () => {
				it( 'If-Modified-Since header is older than current revision', async () => {
					const response = await newRequestBuilder()
						.assertValidRequest()
						.withHeader( 'If-Modified-Since', 'Fri, 1 Apr 2022 12:00:00 GMT' )
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );
			} );

			describe( '304 response', () => {
				it( 'If-Modified-Since header is same as current revision', async () => {
					const response = await newRequestBuilder()
						.assertValidRequest()
						.withHeader( 'If-Modified-Since', `${lastModifiedDate}` )
						.makeRequest();
					assertValid304Response( response, latestRevisionId );
				} );

				it( 'If-Modified-Since header is after current revision', async () => {
					const futureDate = new Date(
						new Date( lastModifiedDate ).getTime() + 5000
					).toUTCString();
					const response = await newRequestBuilder()
						.assertValidRequest()
						.withHeader( 'If-Modified-Since', futureDate )
						.makeRequest();

					assertValid304Response( response, latestRevisionId );
				} );
			} );
		} );

	} );
} );
