'use strict';

const { REST, assert, action, utils, clientFactory } = require( 'api-testing' );
const SwaggerParser = require( '@apidevtools/swagger-parser' );
const { requireExtensions } = require( '../../../../../tests/api-testing/utils' );
const OpenAPIRequestValidator = require( 'openapi-request-validator' ).default;
const OpenAPIRequestCoercer = require( 'openapi-request-coercer' ).default;
const createEntity = require( '../helpers/entityHelper' ).createEntity;

const basePath = 'rest.php/wikibase/v0';
const rest = new REST( basePath );

async function validateRequest( request ) {
	const apiSpec = await SwaggerParser.dereference( './specs/openapi.json' );
	const requestSpec = apiSpec.paths[ '/entities/items/{entity_id}' ].get;
	const specParameters = { parameters: requestSpec.parameters };
	// copy, since the unchanged request is still needed
	const coercedRequest = JSON.parse( JSON.stringify( request ) );

	new OpenAPIRequestCoercer( specParameters ).coerce( coercedRequest );

	return new OpenAPIRequestValidator( requestSpec ).validateRequest( coercedRequest );
}

async function newRequest( request ) {
	return rest.get( request.endpoint, request.query, request.headers );
}

async function newValidRequest( request ) {
	const errors = await validateRequest( request );
	let errorMessage = '';

	if ( typeof errors !== 'undefined' ) {
		const error = errors.errors[ 0 ];
		errorMessage = `[${error.errorCode}] ${error.path} ${error.message} in ${error.location}`;
	}
	assert.isUndefined( errors, errorMessage );

	return newRequest( request );
}

async function newInvalidRequest( request ) {
	const errors = await validateRequest( request );
	assert.isDefined( errors );

	return newRequest( request );
}

function makeEtag( ...revisionIds ) {
	return revisionIds.map( ( revId ) => `"${revId}"` ).join( ',' );
}

const germanLabel = 'a-German-label-' + utils.uniq();
const englishLabel = 'an-English-label-' + utils.uniq();
const englishDescription = 'an-English-description-' + utils.uniq();

describe( 'GET /entities/items/{id} ', () => {
	let testItemId;
	let testModified;
	let testRevisionId;

	async function newValidRequestWithHeader( headers ) {
		return newValidRequest( {
			endpoint: `/entities/items/${testItemId}`,
			// eslint-disable-next-line camelcase
			params: { entity_id: testItemId },
			query: {},
			headers
		} );
	}

	async function newInvalidRequestWithHeader( headers ) {
		return newInvalidRequest( {
			endpoint: `/entities/items/${testItemId}`,
			// eslint-disable-next-line camelcase
			params: { entity_id: testItemId },
			query: {},
			headers
		} );
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
		const response = await newValidRequest( {
			endpoint: `/entities/items/${testItemId}`,
			// eslint-disable-next-line camelcase
			params: { entity_id: testItemId }
		} );

		assertValid200Response( response );
	} );

	it( 'can GET a partial item with single _fields param', async () => {
		const response = await newValidRequest( {
			endpoint: `/entities/items/${testItemId}`,
			// eslint-disable-next-line camelcase
			params: { entity_id: testItemId },
			query: { _fields: 'labels' }
		} );

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
		const response = await newValidRequest( {
			endpoint: `/entities/items/${testItemId}`,
			// eslint-disable-next-line camelcase
			params: { entity_id: testItemId },
			query: { _fields: 'labels,descriptions,aliases' }
		} );

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
				const response = await newValidRequestWithHeader( {
					'If-None-Match': makeEtag( testRevisionId - 1 )
				} );
				assertValid200Response( response );
			} );

			it( 'if the current revision is newer than any of the IDs provided', async () => {
				const response = await newValidRequestWithHeader( {
					'If-None-Match': makeEtag( testRevisionId - 1, testRevisionId - 2 )
				} );
				assertValid200Response( response );
			} );

			it( 'if the provided ETag is not a valid revision ID', async () => {
				const response = await newValidRequestWithHeader( {
					'If-None-Match': '"foo"'
				} );
				assertValid200Response( response );
			} );

			it( 'if all the provided ETags are not valid revision IDs', async () => {
				const response = await newValidRequestWithHeader( {
					'If-None-Match': makeEtag( 'foo', 'bar' )
				} );
				assertValid200Response( response );
			} );

			it( 'if the current revision is newer than any IDs provided (while others are invalid IDs)',
				async () => {
					const response = await newValidRequestWithHeader( {
						'If-None-Match': makeEtag( 'foo', testRevisionId - 1, 'bar' )
					} );
					assertValid200Response( response );
				}
			);

			it( 'if the header is invalid', async () => {
				const response = await newInvalidRequestWithHeader( {
					'If-None-Match': 'not in spec for a If-None-Match header - 200 response'
				} );
				assertValid200Response( response );
			} );

			it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
				const response = await newValidRequestWithHeader( {
					'If-Modified-Since': testModified, // this header on its own would return 304
					'If-None-Match': makeEtag( testRevisionId - 1 )
				} );
				assertValid200Response( response );
			} );

		} );

		describe( '304 response', () => {
			it( 'if the current revision ID is the same as the one provided', async () => {
				const response = await newValidRequestWithHeader( {
					'If-None-Match': makeEtag( testRevisionId )
				} );
				assertValid304Response( response );
			} );

			it( 'if the current revision ID is the same as one of the IDs provided', async () => {
				const response = await newValidRequestWithHeader(
					{ 'If-None-Match': makeEtag( testRevisionId - 1, testRevisionId ) }
				);
				assertValid304Response( response );
			} );

			it( 'if the current revision ID is the same as one of the IDs provided (while others are invalid IDs)',
				async () => {
					const response = await newValidRequestWithHeader( {
						'If-None-Match': makeEtag( 'foo', testRevisionId )
					} );
					assertValid304Response( response );
				}
			);

			it( 'if the header is *', async () => {
				const response = await newValidRequestWithHeader( { 'If-None-Match': '*' } );
				assertValid304Response( response );
			} );

			it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
				const response = await newValidRequestWithHeader( {
					'If-Modified-Since': 'Fri, 1 Apr 2022 12:00:00 GMT', // this header on its own would return 200
					'If-None-Match': makeEtag( testRevisionId )
				} );
				assertValid304Response( response );
			} );
		} );
	} );

	describe( 'If-Modified-Since', () => {
		describe( '200 response', () => {
			it( 'If-Modified-Since header is older than current revision', async () => {
				const response = await newValidRequestWithHeader( {
					'If-Modified-Since': 'Fri, 1 Apr 2022 12:00:00 GMT'
				} );
				assertValid200Response( response );
			} );
		} );

		describe( '304 response', () => {
			it( 'If-Modified-Since header is same as current revision', async () => {
				const response = await newValidRequestWithHeader(
					{ 'If-Modified-Since': `${testModified}` }
				);
				assertValid304Response( response );
			} );

			it( 'If-Modified-Since header is after current revision', async () => {
				const futureDate = new Date(
					new Date( testModified ).getTime() + 5000
				).toUTCString();
				const response = await newValidRequestWithHeader( {
					'If-Modified-Since': futureDate
				} );

				assertValid304Response( response );
			} );
		} );
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const itemId = 'X123';
		const response = await rest.get( `/entities/items/${itemId}` );

		assert.equal( response.status, 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'invalid-item-id' );
		assert.include( response.body.message, itemId );
	} );

	it( '400 error - bad request, invalid field', async () => {
		const itemId = 'Q123';
		const response = await newInvalidRequest( {
			endpoint: `/entities/items/${itemId}`,
			// eslint-disable-next-line camelcase
			params: { entity_id: itemId },
			query: { _fields: 'unknown_field' }
		} );

		assert.equal( response.status, 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'invalid-field' );
		assert.include( response.body.message, 'unknown_field' );
	} );

	it( '404 error - item not found', async () => {
		const itemId = 'Q999999';
		const response = await rest.get( `/entities/items/${itemId}` );

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
