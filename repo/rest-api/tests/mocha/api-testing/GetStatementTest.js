'use strict';

const { REST, assert, action, clientFactory } = require( 'api-testing' );
const SwaggerParser = require( '@apidevtools/swagger-parser' );
const entityHelper = require( '../helpers/entityHelper' );
const { requireExtensions } = require( '../../../../../tests/api-testing/utils' );
const OpenAPIRequestValidator = require( 'openapi-request-validator' ).default;
const OpenAPIRequestCoercer = require( 'openapi-request-coercer' ).default;

const basePath = 'rest.php/wikibase/v0';
const rest = new REST( basePath );

async function validateRequest( request ) {
	const apiSpec = await SwaggerParser.dereference( './specs/openapi.json' );
	const requestSpec = apiSpec.paths[ '/statements/{statement_id}' ].get;
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

describe( 'GET /statements/{statement_id}', () => {
	let testItemId;
	let testStatement;
	let testLastModified;
	let testRevisionId;

	function assertValid200Response( response ) {
		assert.equal( response.status, 200 );
		assert.equal( response.body.id, testStatement.id );
		assert.equal( response.header[ 'last-modified' ], testLastModified );
		assert.equal( response.header.etag, makeEtag( testRevisionId ) );
	}

	before( async () => {
		const createSingleItemResponse = await entityHelper.createSingleItem();
		testItemId = createSingleItemResponse.entity.id;
		const claims = createSingleItemResponse.entity.claims;
		testStatement = Object.values( claims )[ 0 ][ 0 ];

		const itemMetadata = await action.getAnon().action( 'wbgetentities', {
			ids: testItemId
		} );

		testLastModified = new Date( itemMetadata.entities[ testItemId ].modified ).toUTCString();
		testRevisionId = itemMetadata.entities[ testItemId ].lastrevid;

	} );

	it( 'can GET a statement with metadata', async () => {
		const response = await newValidRequest( {
			endpoint: `/statements/${testStatement.id}`,
			// eslint-disable-next-line camelcase
			params: { statement_id: testStatement.id }
		} );

		assertValid200Response( response );
	} );

	describe( '400 error response', () => {
		it( 'statement ID contains invalid entity ID', async () => {
			const statementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newInvalidRequest( {
				endpoint: `/statements/${statementId}`,
				// eslint-disable-next-line camelcase
				params: { statement_id: statementId }
			} );

			assert.equal( response.status, 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'invalid-statement-id' );
			assert.include( response.body.message, statementId );
		} );

		it( 'statement ID is invalid format', async () => {
			const statementId = 'not-a-valid-format';
			const response = await newInvalidRequest( {
				endpoint: `/statements/${statementId}`,
				// eslint-disable-next-line camelcase
				params: { statement_id: statementId }
			} );

			assert.equal( response.status, 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'invalid-statement-id' );
			assert.include( response.body.message, statementId );
		} );

		it( 'statement is not on an item', async () => {
			const statementId = 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newValidRequest( {
				endpoint: `/statements/${statementId}`,
				// eslint-disable-next-line camelcase
				params: { statement_id: statementId }
			} );

			assert.equal( response.status, 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'invalid-statement-id' );
			assert.include( response.body.message, statementId );
		} );
	} );

	describe( '404 error response', () => {
		it( 'statement not found on item', async () => {
			const statementId = testItemId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newValidRequest( {
				endpoint: `/statements/${statementId}`,
				// eslint-disable-next-line camelcase
				params: { statement_id: statementId }
			} );

			assert.equal( response.status, 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
		it( 'item not found', async () => {
			const statementId = 'Q321$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newValidRequest( {
				endpoint: `/statements/${statementId}`,
				// eslint-disable-next-line camelcase
				params: { statement_id: statementId }
			} );

			assert.equal( response.status, 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );
	} );

	describe( 'authentication', () => {

		it( 'has an X-Authenticated-User header with the logged in user', async () => {
			const mindy = await action.mindy();

			const response = await clientFactory.getRESTClient( basePath, mindy )
				.get( `/statements/${testStatement.id}` );

			assertValid200Response( response );
			assert.header( response, 'X-Authenticated-User', mindy.username );
		} );

		describe.skip( 'OAuth', () => { // Skipping due to apache auth header issues. See T305709
			before( requireExtensions( [ 'OAuth' ] ) );

			it( 'responds with an error given an invalid bearer token', async () => {
				const response = await rest.get(
					`/statements/${testStatement.id}`,
					{},
					{ Authorization: 'Bearer this-is-an-invalid-token' }
				);

				assert.equal( response.status, 403 );
			} );

		} );

	} );

} );
