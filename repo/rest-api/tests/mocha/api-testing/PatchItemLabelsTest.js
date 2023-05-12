'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newPatchItemLabelsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { formatLabelsEditSummary } = require( '../helpers/formatEditSummaries' );

function assertValidErrorResponse( response, statusCode, responseBodyErrorCode ) {
	expect( response ).to.have.status( statusCode );
	assert.header( response, 'Content-Language', 'en' );
	assert.strictEqual( response.body.code, responseBodyErrorCode );
}

describe( newPatchItemLabelsRequestBuilder().getRouteDescription(), () => {
	let testItemId;
	let testEnLabel;
	let originalLastModified;
	let originalRevisionId;
	const languageWithExistingLabel = 'en';

	before( async function () {
		testEnLabel = `English Label ${utils.uniq()}`;
		testItemId = ( await entityHelper.createEntity( 'item', {
			labels: [ { language: languageWithExistingLabel, value: testEnLabel } ]
		} ) ).entity.id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		// wait 1s before modifying labels to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '200 OK', () => {
		it( 'can add a label', async () => {
			const label = `neues deutsches label ${utils.uniq()}`;
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[
					{ op: 'add', path: '/de', value: label }
				]
			).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.de, label );
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
			assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
			assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
		} );

		it( 'can patch labels with edit metadata', async () => {
			const label = `new arabic label ${utils.uniq()}`;
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const editSummary = 'I made a patch';
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[
					{
						op: 'add',
						path: '/ar',
						value: label
					}
				]
			).withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', editSummary )
				.withUser( user )
				.assertValidRequest().makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.ar, label );
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
			assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
			assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatLabelsEditSummary( 'update-languages-short', 'ar', editSummary )
			);
		} );
	} );

	describe( '422 error response', () => {
		const makeReplaceExistingLabelPatchOp = ( newLabel ) => ( {
			op: 'replace',
			path: `/${languageWithExistingLabel}`,
			value: newLabel
		} );

		it( 'invalid label', async () => {
			const invalidLabel = 'tab characters \t not allowed';
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( invalidLabel ) ]
			)
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 422, 'patched-label-invalid' );
			assert.include( response.body.message, invalidLabel );
			assert.include( response.body.message, `'${languageWithExistingLabel}'` );
			assert.deepEqual(
				response.body.context,
				{ language: languageWithExistingLabel, value: invalidLabel }
			);
		} );

		it( 'empty label', async () => {
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( '' ) ]
			)
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 422, 'patched-label-empty' );
			assert.strictEqual( response.body.context.language, languageWithExistingLabel );
		} );

		it( 'label too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const tooLongLabel = 'x'.repeat( maxLength + 1 );
			const comment = 'Label too long';
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( tooLongLabel ) ]
			)
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 422, 'patched-label-too-long' );
			assert.strictEqual(
				response.body.message,
				`Changed label for '${languageWithExistingLabel}' must not be more than ${maxLength} characters long`
			);
			assert.deepEqual(
				response.body.context,
				{ value: tooLongLabel, 'character-limit': maxLength, language: languageWithExistingLabel }
			);
		} );

		it( 'invalid language code', async () => {
			const invalidLanguage = 'invalid-language-code';
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[ {
					op: 'add',
					path: `/${invalidLanguage}`,
					value: 'potato'
				} ]
			)
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 422, 'patched-labels-invalid-language-code' );
			assert.include( response.body.message, invalidLanguage );
			assert.deepEqual( response.body.context, { language: invalidLanguage } );
		} );
	} );

	describe( '404 error response', () => {
		it( 'item not found', async () => {
			const itemId = 'Q999999';
			const response = await newPatchItemLabelsRequestBuilder(
				itemId,
				[
					{
						op: 'replace',
						path: '/en',
						value: utils.uniq()
					}
				]
			).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 404 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );
	} );

	describe( '409 error response', () => {
		it( 'item is a redirect', async () => {
			const redirectTarget = testItemId;
			const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

			const response = await newPatchItemLabelsRequestBuilder(
				redirectSource,
				[
					{
						op: 'replace',
						path: '/en',
						value: utils.uniq()
					}
				]
			).assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 409, 'redirected-item' );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
		} );

		it( '"path" field target does not exist', async () => {
			const operation = { op: 'remove', path: '/path/does/not/exist' };

			const response = await newPatchItemLabelsRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 409, 'patch-target-not-found' );
			assert.include( response.body.message, operation.path );
			assert.strictEqual( response.body.context.field, 'path' );
			assert.deepEqual( response.body.context.operation, operation );
		} );

		it( '"from" field target does not exist', async () => {
			const operation = { op: 'copy', from: '/path/does/not/exist', path: '/en' };

			const response = await newPatchItemLabelsRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 409, 'patch-target-not-found' );
			assert.include( response.body.message, operation.from );
			assert.strictEqual( response.body.context.field, 'from' );
			assert.deepEqual( response.body.context.operation, operation );
		} );

		it( 'patch test condition failed', async () => {
			const operation = { op: 'test', path: '/en', value: 'potato' };
			const response = await newPatchItemLabelsRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 409, 'patch-test-failed' );
			assert.deepEqual( response.body.context.operation, operation );
			assert.deepEqual( response.body.context[ 'actual-value' ], testEnLabel );
			assert.include( response.body.message, operation.path );
			assert.include( response.body.message, JSON.stringify( operation.value ) );
			assert.include( response.body.message, testEnLabel );
		} );
	} );

	describe( '415 error response', () => {
		it( 'unsupported media type', async () => {
			const contentType = 'multipart/form-data';
			const response = await newPatchItemLabelsRequestBuilder(
				'Q123',
				[
					{
						op: 'replace',
						path: '/en',
						value: utils.uniq()
					}
				]
			).withHeader( 'content-type', contentType ).makeRequest();

			expect( response ).to.have.status( 415 );
			assert.strictEqual( response.body.message, `Unsupported Content-Type: '${contentType}'` );
		} );
	} );
} );
