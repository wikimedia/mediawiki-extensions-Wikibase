'use strict';

const { REST, assert, action, utils } = require( 'api-testing' );
const SwaggerParser = require( '@apidevtools/swagger-parser' );
const OpenAPIRequestValidator = require( 'openapi-request-validator' ).default;
const OpenAPIRequestCoercer = require( 'openapi-request-coercer' ).default;
const createEntity = require( '../helpers/entityHelper' ).createEntity;

const rest = new REST( 'rest.php/wikibase/v0' );

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
	return rest.get( request.endpoint, request.query );
}

async function newValidRequest( request ) {
	const errors = await validateRequest( request );
	assert.isUndefined( errors );

	return newRequest( request );
}

async function newInvalidRequest( request ) {
	const errors = await validateRequest( request );
	assert.isDefined( errors );

	return newRequest( request );
}

const germanLabel = 'a-German-label-' + utils.uniq();
const englishLabel = 'an-English-label-' + utils.uniq();
const englishDescription = 'an-English-description-' + utils.uniq();

describe( 'GET /entities/items/{id} ', () => {
	let testItemId;
	let testModified;
	let testRevisionId;

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
		}, 'GET' );

		testModified = new Date( getItemMetadata.entities[ testItemId ].modified ).toUTCString();
		testRevisionId = getItemMetadata.entities[ testItemId ].lastrevid;
	} );

	it( 'can GET an item with metadata', async () => {
		const response = await newValidRequest( {
			endpoint: `/entities/items/${testItemId}`,
			// eslint-disable-next-line camelcase
			params: { entity_id: testItemId }
		} );

		assert.equal( response.status, 200 );
		assert.equal( response.body.id, testItemId );
		assert.deepEqual( response.body.aliases, {} ); // expect {}, not []
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, testRevisionId );
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
				de: { language: 'de', value: germanLabel },
				en: { language: 'en', value: englishLabel }
			}
		} );
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, testRevisionId );
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
				de: { language: 'de', value: germanLabel },
				en: { language: 'en', value: englishLabel }
			},
			descriptions: {
				en: { language: 'en', value: englishDescription }
			},
			aliases: {} // expect {}, not []
		} );
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, testRevisionId );
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
} );
