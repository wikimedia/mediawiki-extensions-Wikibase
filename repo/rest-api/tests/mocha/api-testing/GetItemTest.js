'use strict';

const { REST, assert, action, utils } = require( 'api-testing' );
const SwaggerParser = require( '@apidevtools/swagger-parser' );
const OpenAPIRequestValidator = require( 'openapi-request-validator' ).default;
const OpenAPIRequestCoercer = require( 'openapi-request-coercer' ).default;
const createEntity = require( '../helpers/entityHelper' ).createEntity;

const germanLabel = 'a-German-label-' + utils.uniq();
const englishLabel = 'an-English-label-' + utils.uniq();
const englishDescription = 'an-English-description-' + utils.uniq();

function validateRequest( requestSpec, request ) {
	const specParameters = { parameters: requestSpec.parameters };
	// copy, since the unchanged request is still needed
	const coercedRequest = JSON.parse( JSON.stringify( request ) );
	new OpenAPIRequestCoercer( specParameters ).coerce( coercedRequest );

	return new OpenAPIRequestValidator( requestSpec ).validateRequest( coercedRequest );
}

describe( 'GET /entities/items/{id} ', () => {
	let getItemSpec;
	let testItemId;
	let testModified;
	let testRevisionId;
	let rest;
	const basePath = 'rest.php/wikibase/v0';

	before( async () => {
		const apiSpec = await SwaggerParser.dereference( './specs/openapi.json' );
		getItemSpec = apiSpec.paths[ '/entities/items/{entity_id}' ].get;

		rest = new REST( basePath );

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
		const request = {
			endpoint: `/entities/items/${testItemId}`,
			// eslint-disable-next-line camelcase
			params: { entity_id: testItemId }
		};

		const errors = validateRequest( getItemSpec, request );
		assert.isUndefined( errors );

		const response = await rest.get( request.endpoint );

		assert.equal( response.status, 200 );
		assert.equal( response.body.id, testItemId );
		assert.deepEqual( response.body.aliases, {} ); // expect {}, not []
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, testRevisionId );
	} );

	it( 'can GET a partial item with single _fields param', async () => {
		const request = {
			endpoint: `/entities/items/${testItemId}`,
			// eslint-disable-next-line camelcase
			params: { entity_id: testItemId },
			query: { _fields: 'labels' }
		};

		const errors = validateRequest( getItemSpec, request );
		assert.isUndefined( errors );

		const response = await rest.get( request.endpoint, request.query );

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
		const request = {
			endpoint: `/entities/items/${testItemId}`,
			// eslint-disable-next-line camelcase
			params: { entity_id: testItemId },
			query: { _fields: 'labels,descriptions,aliases' }
		};

		const errors = validateRequest( getItemSpec, request );
		assert.isUndefined( errors );

		const response = await rest.get( request.endpoint, request.query );

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
		const request = {
			endpoint: `/entities/items/${itemId}`,
			// eslint-disable-next-line camelcase
			params: { entity_id: itemId },
			query: { _fields: 'unknown_field' }
		};

		const errors = validateRequest( getItemSpec, request );
		assert.isDefined( errors ); // expect request validation errors

		const response = await rest.get( request.endpoint, request.query );

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
