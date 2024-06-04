'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const { assert, utils } = require( 'api-testing' );
const { newPatchItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const entityHelper = require( '../helpers/entityHelper' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const { formatWholeEntityEditSummary } = require( '../helpers/formatEditSummaries' );

describe( newPatchItemRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let originalLastModified;
	let originalRevisionId;
	let predicatePropertyId;
	const testEnglishLabel = `some-label-${utils.uniq()}`;

	before( async function () {
		testItemId = ( await entityHelper.createEntity( 'item', {
			labels: [ { language: 'en', value: testEnglishLabel } ],
			descriptions: [ { language: 'en', value: `some-description-${utils.uniq()}` } ],
			aliases: [ { language: 'fr', value: 'croissant' } ]
		} ) ).entity.id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		predicatePropertyId = ( await entityHelper.createEntity( 'property', { datatype: 'string' } ) ).entity.id;

		// wait 1s before next test to ensure the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '200 OK', () => {

		it( 'can patch an item', async () => {
			const newLabel = `neues deutsches label ${utils.uniq()}`;
			const updatedDescription = `changed description ${utils.uniq()}`;
			const newStatementValue = 'new statement';
			const editSummary = 'I made a patch';
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[
					{ op: 'add', path: '/labels/de', value: newLabel },
					{ op: 'replace', path: '/descriptions/en', value: updatedDescription },
					{ op: 'remove', path: '/aliases/fr' },
					{
						op: 'add',
						path: `/statements/${predicatePropertyId}`,
						value: [ {
							property: { id: predicatePropertyId },
							value: { type: 'value', content: newStatementValue }
						} ]
					}
				]
			).withJsonBodyParam( 'comment', editSummary ).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.id, testItemId );
			assert.strictEqual( response.body.labels.de, newLabel );
			assert.strictEqual( response.body.descriptions.en, updatedDescription );
			assert.isEmpty( response.body.aliases );
			assert.strictEqual( response.body.statements[ predicatePropertyId ][ 0 ].value.content, newStatementValue );
			assert.match(
				response.body.statements[ predicatePropertyId ][ 0 ].id,
				new RegExp( `^${testItemId}\\$[A-Z0-9]{8}(-[A-Z0-9]{4}){3}-[A-Z0-9]{12}$`, 'i' )
			);
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
			assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
			assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.strictEqual(
				editMetadata.comment,
				formatWholeEntityEditSummary( 'update-languages-and-other-short', 'de, en, fr', editSummary )
			);
		} );

	} );

	describe( '400 error response ', () => {

		it( 'item ID is invalid', async () => {
			const itemId = 'X123';
			const response = await newPatchItemRequestBuilder( itemId, [] )
				.assertInvalidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-item-id' );
			assert.include( response.body.message, itemId );
		} );

		testValidatesPatch( ( patch ) => newPatchItemRequestBuilder( testItemId, patch ) );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newPatchItemRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newPatchItemRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newPatchItemRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag', async () => {
			const response = await newPatchItemRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'bot', 'should be a boolean' )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newPatchItemRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'comment' );
			assert.strictEqual( response.body.expectedType, 'string' );
		} );
	} );

	describe( '404 error response', () => {
		it( 'item not found', async () => {
			const itemId = 'Q99999';
			const response = await newPatchItemRequestBuilder( itemId, [] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 404, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );
	} );

	describe( '409 error response', () => {

		it( '"path" field target does not exist', async () => {
			const operation = { op: 'remove', path: '/path/does/not/exist' };

			const response = await newPatchItemRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-target-not-found', { field: 'path', operation } );
			assert.include( response.body.message, operation.path );
		} );

		it( '"from" field target does not exist', async () => {
			const operation = { op: 'copy', from: '/path/does/not/exist', path: '/labels/en' };

			const response = await newPatchItemRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-target-not-found', { field: 'from', operation } );
			assert.include( response.body.message, operation.from );
		} );

		it( 'patch test condition failed', async () => {
			const operation = { op: 'test', path: '/labels/en', value: 'german-label' };
			const response = await newPatchItemRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-test-failed', { operation, 'actual-value': testEnglishLabel } );
			assert.include( response.body.message, operation.path );
			assert.include( response.body.message, JSON.stringify( operation.value ) );
			assert.include( response.body.message, testEnglishLabel );
		} );

		it( 'item is a redirect', async () => {
			const redirectTarget = testItemId;
			const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

			const response = await newPatchItemRequestBuilder( redirectSource, [] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'redirected-item' );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
		} );
	} );

	describe( '422 error response', () => {
		it( 'after patching: invalid operation change item id', async () => {
			const patch = [
				{ op: 'replace', path: '/id', value: 'Q123' }
			];

			const response = await newPatchItemRequestBuilder( testItemId, patch )
				.assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-item-invalid-operation-change-item-id' );
			assert.strictEqual( response.body.message, 'Cannot change the ID of the existing item' );
		} );

		it( 'after patching labels: invalid field', async () => {
			const patch = [
				{ op: 'replace', path: '/labels', value: 'invalid-labels' }
			];

			const response = await newPatchItemRequestBuilder( testItemId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: 'labels', value: 'invalid-labels' };
			assertValidError( response, 422, 'patched-item-invalid-field', context );
			assert.strictEqual( response.body.message, "Invalid input for 'labels' in the patched item" );
		} );

		it( 'after patching: unexpected field', async () => {
			const patch = [ { op: 'add', path: '/foo', value: 'bar' } ];
			const response = await newPatchItemRequestBuilder( testItemId, patch )
				.assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-item-unexpected-field' );
			assert.strictEqual( response.body.message, "The patched item contains an unexpected field: 'foo'" );
		} );
	} );

} );
