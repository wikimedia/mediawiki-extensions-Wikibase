'use strict';

const { assert, action, clientFactory, utils } = require( 'api-testing' );
const entityHelper = require( '../helpers/entityHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const { requireExtensions } = require( '../../../../../tests/api-testing/utils' );

const basePath = 'rest.php/wikibase/v0';

function newAddItemStatementRequestBuilder( itemId, statement ) {
	return new RequestBuilder()
		.withRoute( '/entities/items/{item_id}/statements' )
		.withPathParam( 'item_id', itemId )
		.withHeader( 'content-type', 'application/json' )
		.withJsonBodyParam( 'statement', statement );
}

function makeEtag( ...revisionIds ) {
	return revisionIds.map( ( revId ) => `"${revId}"` ).join( ',' );
}

describe( 'POST /entities/items/{item_id}/statements', () => {
	let testItemId;
	let originalLastModified;
	let originalRevisionId;
	let testStatement;

	function assertValid201Response( response ) {
		assert.strictEqual( response.status, 201 );
		assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
		assert.deepInclude( response.body.mainsnak, testStatement.mainsnak );
	}

	before( async () => {
		const createEntityResponse = await entityHelper.createEntity( 'item', {} );
		testItemId = createEntityResponse.entity.id;

		const entities = await action.getAnon().action( 'wbgetentities', {
			ids: testItemId
		} );
		const item = entities.entities[ testItemId ];

		originalLastModified = new Date( item.modified );
		originalRevisionId = item.lastrevid;

		const stringPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;

		testStatement = {
			// TODO: 'type' is currently required for validation to pass
			type: 'statement',
			mainsnak: {
				snaktype: 'value',
				property: stringPropertyId,
				datavalue: {
					type: 'string',
					value: `unique-string-value-${utils.uniq()}`
				}
			},
			rank: 'preferred',
			qualifiers: {},
			'qualifiers-order': [],
			references: []
		};

		// wait 1s before adding any statements to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '201 success response ', () => {
		it( 'can add a statement to an item with bot and tags omitted', async () => {
			const response = await newAddItemStatementRequestBuilder( testItemId, testStatement )
				.assertValidRequest()
				.makeRequest( 'POST' );

			assertValid201Response( response );
		} );
		it( 'can add a statement to an item with bot and tags provided', async () => {
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const response = await newAddItemStatementRequestBuilder( testItemId, testStatement )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.assertValidRequest()
				.makeRequest( 'POST' );

			assertValid201Response( response );
			// check that the tags and bot flag have been set correctly
			const recentChanges = await action.getAnon().action( 'query', {
				list: 'recentchanges',
				rctitle: `Item:${testItemId}`,
				rclimit: 1,
				rcprop: 'tags|flags'
			} );
			const editMetadata = recentChanges.query.recentchanges[ 0 ];
			assert.deepEqual( editMetadata.tags, [ tag ] );
			assert.property( editMetadata, 'bot' );
		} );
	} );

	describe( '400 error response', () => {
		it( 'invalid Item ID', async () => {
			const itemId = 'X123';
			const response = await newAddItemStatementRequestBuilder( itemId, testStatement )
				.assertInvalidRequest()
				.makeRequest( 'POST' );

			assert.strictEqual( response.status, 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'invalid-item-id' );
			assert.include( response.body.message, itemId );
		} );
		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newAddItemStatementRequestBuilder( testItemId, testStatement )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] )
				.assertValidRequest()
				.makeRequest( 'POST' );

			assert.strictEqual( response.status, 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );
		// Skipping due to JsonBodyValidator not currently validating types. See T305973
		it.skip( 'invalid bot flag', async () => {
			const invalidBotFlag = 'should be a boolean';
			const response = await newAddItemStatementRequestBuilder( testItemId, testStatement )
				.withJsonBodyParam( 'bot', invalidBotFlag )
				.assertInvalidRequest()
				.makeRequest( 'POST' );

			assert.strictEqual( response.status, 400 );
			// TODO: validating this depends on if it is a WB or MW error response
			// assert.strictEqual( response.header[ 'content-language' ], 'en' );
			// assert.strictEqual( response.body.code, 'invalid-bot-flag' );
			// assert.include( response.body.message, invalidBotFlag );
		} );
		it( 'invalid statement data', async () => {
			const invalidStatement = {
				invalidKey: []
			};
			const response = await newAddItemStatementRequestBuilder( testItemId, invalidStatement )
				.assertInvalidRequest()
				.makeRequest( 'POST' );

			assert.strictEqual( response.status, 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'invalid-statement-data' );
		} );
	} );

	describe( '404 error response', () => {
		it( 'item not found', async () => {
			const itemId = 'Q999999';
			const response = await newAddItemStatementRequestBuilder( itemId, testStatement )
				.assertValidRequest()
				.makeRequest( 'POST' );

			assert.strictEqual( response.status, 404 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );
	} );

	describe( 'authentication', () => {

		it( 'has an X-Authenticated-User header with the logged in user', async () => {
			const mindy = await action.mindy();
			const response = await clientFactory.getRESTClient( basePath, mindy ).post(
				`/entities/items/${testItemId}/statements`,
				{ statement: testStatement },
				{ 'content-type': 'application/json' }
			);

			assertValid201Response( response );
			assert.header( response, 'X-Authenticated-User', mindy.username );
		} );

		describe.skip( 'OAuth', () => { // Skipping due to apache auth header issues. See T305709
			before( requireExtensions( [ 'OAuth' ] ) );

			it( 'responds with an error given an invalid bearer token', async () => {
				const response = newAddItemStatementRequestBuilder( testItemId, testStatement )
					.withHeader( 'Authorization', 'Bearer this-is-an-invalid-token' )
					.makeRequest();

				assert.strictEqual( response.status, 403 );
			} );

		} );

	} );
} );
