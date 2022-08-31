'use strict';

const { assert, action } = require( 'api-testing' );
const entityHelper = require( '../helpers/entityHelper' );
const hasJsonDiffLib = require( '../helpers/hasJsonDiffLib' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );

function newPatchItemStatementRequestBuilder( itemId, statementId, patch ) {
	return new RequestBuilder()
		.withRoute( 'PATCH', '/entities/items/{item_id}/statements/{statement_id}' )
		.withPathParam( 'item_id', itemId )
		.withPathParam( 'statement_id', statementId )
		.withJsonBodyParam( 'patch', patch );
}

function makeEtag( ...revisionIds ) {
	return revisionIds.map( ( revId ) => `"${revId}"` ).join( ',' );
}

describe( 'PATCH /entities/items/{item_id}/statements/{statement_id}', () => {
	let testItemId;
	let testPropertyId;
	let testStatementId;
	let originalLastModified;
	let originalRevisionId;

	before( async function () {
		if ( !hasJsonDiffLib() ) {
			this.skip(); // awaiting security review (T316245)
		}

		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;

		const createItemResponse = await entityHelper.createEntity( 'item', {
			claims: [ entityHelper.newStatementWithRandomStringValue( testPropertyId ) ]
		} );
		testItemId = createItemResponse.entity.id;
		testStatementId = createItemResponse.entity.claims[ testPropertyId ][ 0 ].id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		// wait 1s before adding any statements to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	function assertValid200Response( response ) {
		assert.strictEqual( response.status, 200 );
		assert.strictEqual( response.body.id, testStatementId );
		assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
	}

	describe( '200 success response', () => {

		afterEach( async () => {
			await new RequestBuilder() // reset after successful edit
				.withRoute( 'PUT', '/statements/{statement_id}' )
				.withPathParam( 'statement_id', testStatementId )
				.withJsonBodyParam(
					'statement',
					entityHelper.newStatementWithRandomStringValue( testPropertyId )
				)
				.makeRequest();
		} );

		it( 'can patch a statement', async () => {
			const expectedValue = 'i been patched!!';
			const response = await newPatchItemStatementRequestBuilder( testItemId, testStatementId, [
				{
					op: 'replace',
					path: '/mainsnak/datavalue/value',
					value: expectedValue
				}
			] ).assertValidRequest().makeRequest();

			assertValid200Response( response );
			assert.strictEqual( response.body.mainsnak.datavalue.value, expectedValue );
		} );

		it( 'allows content-type application/json-patch+json', async () => {
			const expectedValue = 'i been patched again!!';
			const response = await newPatchItemStatementRequestBuilder( testItemId, testStatementId, [
				{
					op: 'replace',
					path: '/mainsnak/datavalue/value',
					value: expectedValue
				}
			] )
				.withHeader( 'content-type', 'application/json-patch+json' )
				.assertValidRequest().makeRequest();

			assertValid200Response( response );
			assert.strictEqual( response.body.mainsnak.datavalue.value, expectedValue );
		} );

		it( 'can patch a statement with edit metadata', async () => {
			const user = await action.mindy();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const editSummary = 'i made a patch';
			const expectedValue = `${user.username} was here`;
			const response = await newPatchItemStatementRequestBuilder( testItemId, testStatementId, [
				{
					op: 'replace',
					path: '/mainsnak/datavalue/value',
					value: expectedValue
				}
			] ).withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', editSummary )
				.withUser( user )
				.assertValidRequest().makeRequest();

			assertValid200Response( response );
			assert.strictEqual( response.body.mainsnak.datavalue.value, expectedValue );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.deepEqual( editMetadata.tags, [ tag ] );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual( editMetadata.comment, editSummary );
			assert.strictEqual( editMetadata.user, user.username );
		} );

	} );

} );
