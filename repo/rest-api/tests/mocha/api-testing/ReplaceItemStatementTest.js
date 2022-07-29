'use strict';

const { assert, action, utils, clientFactory } = require( 'api-testing' );
const entityHelper = require( '../helpers/entityHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const { requireExtensions } = require( '../../../../../tests/api-testing/utils' );

function newReplaceItemStatementRequestBuilder( itemId, statementId, statement ) {
	return new RequestBuilder()
		.withRoute( 'PUT', '/entities/items/{item_id}/statements/{statement_id}' )
		.withPathParam( 'item_id', itemId )
		.withPathParam( 'statement_id', statementId )
		.withHeader( 'content-type', 'application/json' )
		.withJsonBodyParam( 'statement', statement );
}

function newStatementWithRandomStringValue( property ) {
	return {
		mainsnak: {
			snaktype: 'value',
			datavalue: {
				type: 'string',
				value: 'random-string-value-' + utils.uniq()
			},
			property
		},
		type: 'statement'
	};
}

function makeEtag( ...revisionIds ) {
	return revisionIds.map( ( revId ) => `"${revId}"` ).join( ',' );
}

async function getLatestEditMetadata( itemId ) {
	const recentChanges = await action.getAnon().action( 'query', {
		list: 'recentchanges',
		rctitle: `Item:${itemId}`,
		rclimit: 1,
		rcprop: 'tags|flags|comment'
	} );

	return recentChanges.query.recentchanges[ 0 ];
}

describe( 'PUT /entities/items/{item_id}/statements/{statement_id}', () => {
	let testItemId;
	let testStatementId;
	let testPropertyId;
	let originalLastModified;
	let originalRevisionId;

	function assertValid200Response( response ) {
		assert.strictEqual( response.status, 200 );
		assert.strictEqual( response.body.id, testStatementId );
		assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
	}

	before( async () => {
		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
		const createEntityResponse = await entityHelper.createEntity( 'item', {
			claims: [ {
				mainsnak: {
					snaktype: 'novalue',
					property: testPropertyId
				},
				type: 'statement'
			} ]
		} );
		testItemId = createEntityResponse.entity.id;
		testStatementId = createEntityResponse.entity.claims[ testPropertyId ][ 0 ].id;

		const entities = await action.getAnon().action( 'wbgetentities', {
			ids: testItemId
		} );
		const item = entities.entities[ testItemId ];

		originalLastModified = new Date( item.modified );
		originalRevisionId = item.lastrevid;

		// wait 1s before modifications to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '200 success response ', () => {
		it( 'can replace a statement to an item with edit metadata omitted', async () => {
			const statementSerialization = newStatementWithRandomStringValue( testPropertyId );
			const response = await newReplaceItemStatementRequestBuilder(
				testItemId,
				testStatementId,
				statementSerialization
			).assertValidRequest().makeRequest();

			assertValid200Response( response );

			assert.deepEqual(
				response.body.mainsnak.datavalue,
				statementSerialization.mainsnak.datavalue
			);
			const { comment } = await getLatestEditMetadata( testItemId );
			assert.strictEqual( comment, 'Wikibase REST API edit' );
		} );

		it( 'can replace a statement to an item with edit metadata provided', async () => {
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const editSummary = 'omg look, I made an edit!';
			const statementSerialization = newStatementWithRandomStringValue( testPropertyId );
			const response = await newReplaceItemStatementRequestBuilder(
				testItemId,
				testStatementId,
				statementSerialization
			).withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', editSummary )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response );
			assert.deepEqual(
				response.body.mainsnak.datavalue,
				statementSerialization.mainsnak.datavalue
			);

			const editMetadata = await getLatestEditMetadata( testItemId );
			assert.deepEqual( editMetadata.tags, [ tag ] );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual( editMetadata.comment, editSummary );
		} );

		it( 'is idempotent: repeating the same request only results in one edit', async () => {
			const requestTemplate = newReplaceItemStatementRequestBuilder(
				testItemId,
				testStatementId,
				newStatementWithRandomStringValue( testPropertyId )
			).assertValidRequest();

			const response1 = await requestTemplate.makeRequest();
			const response2 = await requestTemplate.makeRequest();

			assertValid200Response( response1 );
			assertValid200Response( response2 );

			assert.strictEqual( response2.headers.etag, response1.headers.etag );
			assert.strictEqual( response2.headers[ 'last-modified' ], response1.headers[ 'last-modified' ] );
			assert.deepEqual( response2.body, response1.body );
		} );

		it( 'replaces the statement in place without changing the order', async () => {
			// This is tested here by creating an item with 3 statements, replacing the middle one
			// and then checking that it's still in the middle afterwards.
			const item = ( await entityHelper.createEntity( 'item', {
				claims: [
					newStatementWithRandomStringValue( testPropertyId ),
					newStatementWithRandomStringValue( testPropertyId ),
					newStatementWithRandomStringValue( testPropertyId )
				]
			} ) ).entity;
			const originalSecondStatement = item.claims[ testPropertyId ][ 1 ];
			const newSecondStatement = newStatementWithRandomStringValue( testPropertyId );

			await newReplaceItemStatementRequestBuilder(
				item.id,
				originalSecondStatement.id,
				newSecondStatement
			).makeRequest();

			const actualSecondStatement = ( await new RequestBuilder()
				.withRoute( 'GET', '/entities/items/{item_id}/statements' )
				.withPathParam( 'item_id', item.id )
				.makeRequest() ).body[ testPropertyId ][ 1 ];

			assert.strictEqual( actualSecondStatement.id, originalSecondStatement.id );
			assert.strictEqual(
				actualSecondStatement.mainsnak.datavalue.value,
				newSecondStatement.mainsnak.datavalue.value
			);
			assert.notEqual(
				actualSecondStatement.mainsnak.datavalue.value,
				originalSecondStatement.mainsnak.datavalue.value
			);
		} );

	} );

	describe( '404 error response', () => {
		it( 'statement not found on item', async () => {
			const statementId = testItemId + '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
			const response = await newReplaceItemStatementRequestBuilder( testItemId, statementId )
				.withJsonBodyParam( 'statement', newStatementWithRandomStringValue( testPropertyId ) )
				.assertValidRequest()
				.makeRequest();

			assert.equal( response.status, 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'statement-not-found' );
			assert.include( response.body.message, statementId );
		} );

		it( 'item not found', async () => {
			const itemId = 'Q9999999';
			const statementId = `${itemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;
			const response = await newReplaceItemStatementRequestBuilder( itemId, statementId )
				.withJsonBodyParam( 'statement', newStatementWithRandomStringValue( testPropertyId ) )
				.assertValidRequest()
				.makeRequest();

			assert.equal( response.status, 404 );
			assert.header( response, 'Content-Language', 'en' );
			assert.equal( response.body.code, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );
	} );

	describe( 'authentication', () => {

		it( 'has an X-Authenticated-User header with the logged in user', async () => {
			const mindy = await action.mindy();
			const response = await clientFactory.getRESTClient( 'rest.php/wikibase/v0', mindy ).put(
				`/entities/items/${testItemId}/statements/${testStatementId}`,
				{ statement: newStatementWithRandomStringValue( testPropertyId ) },
				{ 'content-type': 'application/json' }
			);

			assertValid200Response( response );
			assert.header( response, 'X-Authenticated-User', mindy.username );
		} );

		describe.skip( 'OAuth', () => { // Skipping due to apache auth header issues. See T305709
			before( requireExtensions( [ 'OAuth' ] ) );

			it( 'responds with an error given an invalid bearer token', async () => {
				const response = newReplaceItemStatementRequestBuilder(
					testItemId,
					testStatementId,
					newStatementWithRandomStringValue( testPropertyId )
				)
					.withHeader( 'Authorization', 'Bearer this-is-an-invalid-token' )
					.makeRequest();

				assert.strictEqual( response.status, 403 );
			} );

		} );

	} );

} );
