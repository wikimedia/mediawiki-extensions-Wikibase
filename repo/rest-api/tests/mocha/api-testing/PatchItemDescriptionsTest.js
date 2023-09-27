'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newPatchItemDescriptionsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { formatLabelsEditSummary } = require( '../helpers/formatEditSummaries' );

function assertValidErrorResponse( response, statusCode, responseBodyErrorCode, context = null ) {
	expect( response ).to.have.status( statusCode );
	assert.header( response, 'Content-Language', 'en' );
	assert.strictEqual( response.body.code, responseBodyErrorCode );
	if ( context === null ) {
		assert.notProperty( response.body, 'context' );
	} else {
		assert.deepStrictEqual( response.body.context, context );
	}
}

describe( newPatchItemDescriptionsRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let testEnDescription;
	const languageWithExistingLabel = 'en';

	before( async function () {
		testEnDescription = `English Description ${utils.uniq()}`;
		testItemId = ( await entityHelper.createEntity( 'item', {
			descriptions: [ { language: languageWithExistingLabel, value: testEnDescription } ]
		} ) ).entity.id;

		// wait 1s before modifying labels to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '200 OK', () => {
		it( 'can add a description', async () => {
			const description = `Neues Deutsches Beschreibung ${utils.uniq()}`;
			const response = await newPatchItemDescriptionsRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/de', value: description } ]
			).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.de, description );
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		} );

		it( 'trims whitespace around the description', async () => {
			const description = `spacey ${utils.uniq()}`;
			const response = await newPatchItemDescriptionsRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/de', value: ` \t${description}  ` } ]
			).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.de, description );
		} );

		it( 'can patch descriptions with edit metadata', async () => {
			const description = `${utils.uniq()} وصف عربي جديد`;
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const comment = 'I made a patch';
			const response = await newPatchItemDescriptionsRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/ar', value: description } ]
			)
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.ar, description );
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatLabelsEditSummary( 'update-languages-short', 'ar', comment )
			);
		} );
	} );

	describe( '400 error response', () => {

		it( 'invalid item id', async () => {
			const itemId = testItemId.replace( 'Q', 'P' );
			const response = await newPatchItemDescriptionsRequestBuilder( itemId, [] )
				.assertInvalidRequest().makeRequest();

			assertValidErrorResponse( response, 400, 'invalid-item-id' );
			assert.include( response.body.message, itemId );
		} );

		it( 'invalid patch', async () => {
			const invalidPatch = { foo: 'this is not a valid JSON Patch' };
			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, invalidPatch )
				.assertInvalidRequest().makeRequest();

			assertValidErrorResponse( response, 400, 'invalid-patch' );
		} );

		it( "invalid patch - missing 'op' field", async () => {
			const invalidOperation = { path: '/a/b/c', value: 'test' };
			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [ invalidOperation ] )
				.assertInvalidRequest().makeRequest();

			assertValidErrorResponse(
				response,
				400,
				'missing-json-patch-field',
				{ operation: invalidOperation, field: 'op' }
			);
			assert.include( response.body.message, "'op'" );
		} );

		it( "invalid patch - missing 'path' field", async () => {
			const invalidOperation = { op: 'remove' };
			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [ invalidOperation ] )
				.assertInvalidRequest().makeRequest();

			assertValidErrorResponse(
				response,
				400,
				'missing-json-patch-field',
				{ operation: invalidOperation, field: 'path' }
			);
			assert.include( response.body.message, "'path'" );
		} );

		it( "invalid patch - missing 'value' field", async () => {
			const invalidOperation = { op: 'add', path: '/a/b/c' };
			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [ invalidOperation ] )
				.makeRequest();

			assertValidErrorResponse(
				response,
				400,
				'missing-json-patch-field',
				{ operation: invalidOperation, field: 'value' }
			);
			assert.include( response.body.message, "'value'" );
		} );

		it( "invalid patch - missing 'from' field", async () => {
			const invalidOperation = { op: 'move', path: '/a/b/c' };
			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [ invalidOperation ] )
				.makeRequest();

			assertValidErrorResponse(
				response,
				400,
				'missing-json-patch-field',
				{ operation: invalidOperation, field: 'from' }
			);
			assert.include( response.body.message, "'from'" );
		} );

		it( "invalid patch - invalid 'op' field", async () => {
			const invalidOperation = { op: 'foobar', path: '/a/b/c', value: 'test' };
			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [ invalidOperation ] )
				.assertInvalidRequest().makeRequest();

			assertValidErrorResponse( response, 400, 'invalid-patch-operation', { operation: invalidOperation } );
			assert.include( response.body.message, "'foobar'" );
		} );

		it( "invalid patch - 'op' is not a string", async () => {
			const invalidOperation = { op: { foo: [ 'bar' ], baz: 42 }, path: '/a/b/c', value: 'test' };
			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [ invalidOperation ] )
				.assertInvalidRequest().makeRequest();

			assertValidErrorResponse(
				response,
				400,
				'invalid-patch-field-type',
				{ operation: invalidOperation, field: 'op' }
			);
			assert.include( response.body.message, "'op'" );
			assert.deepEqual( response.body.context.operation, invalidOperation );
			assert.strictEqual( response.body.context.field, 'op' );
		} );

		it( "invalid patch - 'path' is not a string", async () => {
			const invalidOperation = { op: 'add', path: { foo: [ 'bar' ], baz: 42 }, value: 'test' };
			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [ invalidOperation ] )
				.assertInvalidRequest().makeRequest();

			assertValidErrorResponse(
				response,
				400,
				'invalid-patch-field-type',
				{ operation: invalidOperation, field: 'path' }
			);
			assert.include( response.body.message, "'path'" );
		} );

		it( "invalid patch - 'from' is not a string", async () => {
			const invalidOperation = { op: 'move', from: { foo: [ 'bar' ], baz: 42 }, path: '/a/b/c' };
			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [ invalidOperation ] )
				.makeRequest();

			assertValidErrorResponse(
				response,
				400,
				'invalid-patch-field-type',
				{ operation: invalidOperation, field: 'from' }
			);
			assert.include( response.body.message, "'from'" );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 400, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 400, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'comment' );
			assert.strictEqual( response.body.expectedType, 'string' );
		} );
	} );

} );
