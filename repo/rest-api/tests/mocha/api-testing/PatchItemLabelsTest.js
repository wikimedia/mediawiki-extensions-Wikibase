'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const entityHelper = require( '../helpers/entityHelper' );
const { newPatchItemLabelsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { formatTermsEditSummary } = require( '../helpers/formatEditSummaries' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );

describe( newPatchItemLabelsRequestBuilder().getRouteDescription(), () => {
	let testItemId;
	let testEnLabel;
	let testEnDescription;
	let originalLastModified;
	let originalRevisionId;
	const languageWithExistingLabel = 'en';

	before( async function () {
		testEnLabel = `English Label ${utils.uniq()}`;
		testEnDescription = `English Description ${utils.uniq()}`;
		testItemId = ( await entityHelper.createEntity( 'item', {
			labels: [ { language: languageWithExistingLabel, value: testEnLabel } ],
			descriptions: [ { language: languageWithExistingLabel, value: testEnDescription } ]
		} ) ).entity.id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		// wait 1s before next test to ensure the last-modified timestamps are different
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

		it( 'trims whitespace around the label', async () => {
			const label = `spacey ${utils.uniq()}`;
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[
					{ op: 'add', path: '/de', value: `  ${label}  ` }
				]
			).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.de, label );
		} );

		it( 'allows content-type application/json-patch+json', async () => {
			const label = `neues deutsches label ${utils.uniq()}`;
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[
					{ op: 'add', path: '/de', value: label }
				]
			)
				.withHeader( 'content-type', 'application/json-patch+json' )
				.assertValidRequest().makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.de, label );
		} );

		it( 'can patch labels with edit metadata', async () => {
			const label = `new arabic label ${utils.uniq()}`;
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
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
				formatTermsEditSummary( 'update-languages-short', 'ar', editSummary )
			);
		} );

		it( 'can add a "mul" label', async () => {
			const label = `mul-label-${utils.uniq()}`;
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/mul', value: label } ]
			).withHeader( 'X-Wikibase-Ci-Enable-Mul', 'true' ).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.mul, label );
		} );
	} );

	describe( '422 error response', () => {
		const makeReplaceExistingLabelPatchOp = ( newLabel ) => ( {
			op: 'replace',
			path: `/${languageWithExistingLabel}`,
			value: newLabel
		} );

		it( 'invalid labels type', async () => {
			const invalidLabels = 'foo';
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[ { op: 'replace', path: '', value: invalidLabels } ]
			)
				.assertValidRequest()
				.makeRequest();

			const context = { path: '', value: invalidLabels };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'labels is not an object', async () => {
			const invalidLabels = [ 'list, not an object' ];
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[ { op: 'replace', path: '', value: invalidLabels } ]
			)
				.assertValidRequest()
				.makeRequest();

			const context = { path: '', value: invalidLabels };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'invalid label', async () => {
			const invalidLabel = 'tab characters \t not allowed';
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( invalidLabel ) ]
			)
				.assertValidRequest()
				.makeRequest();

			const context = { path: `/${languageWithExistingLabel}`, value: invalidLabel };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'invalid label type', async () => {
			const invalidLabel = { object: 'not allowed' };
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( invalidLabel ) ]
			)
				.assertValidRequest()
				.makeRequest();

			const context = { path: `/${languageWithExistingLabel}`, value: invalidLabel };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'empty label', async () => {
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( '' ) ]
			)
				.assertValidRequest()
				.makeRequest();

			const context = { path: `/${languageWithExistingLabel}`, value: '' };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
		} );

		it( 'empty label after trimming whitespace in the input', async () => {
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( ' ' ) ]
			)
				.assertValidRequest()
				.makeRequest();

			const context = { path: `/${languageWithExistingLabel}`, value: '' };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
		} );

		it( 'label too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const comment = 'Label too long';
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( 'x'.repeat( maxLength + 1 ) ) ]
			)
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			const context = { path: '/en', limit: maxLength };
			assertValidError( response, 422, 'patch-result-value-too-long', context );
			assert.strictEqual( response.body.message, 'Patched value is too long' );
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

			assertValidError( response, 422, 'patch-result-invalid-key', { path: '', key: invalidLanguage } );
		} );

		it( 'patched label and description already exists in a different item', async () => {
			const languageCode = 'en';
			const label = `test-label-${utils.uniq()}`;
			const description = `test-description-${utils.uniq()}`;
			const existingEntityResponse = await entityHelper.createEntity( 'item', {
				labels: [ { language: languageCode, value: label } ],
				descriptions: [ { language: languageCode, value: description } ]
			} );
			const existingItemId = existingEntityResponse.entity.id;
			const createEntityResponse = await entityHelper.createEntity( 'item', {
				labels: [ { language: languageCode, value: `label-to-be-replaced-${utils.uniq()}` } ],
				descriptions: [ { language: languageCode, value: description } ]
			} );
			const itemIdToBePatched = createEntityResponse.entity.id;

			const response = await newPatchItemLabelsRequestBuilder(
				itemIdToBePatched,
				[ {
					op: 'replace',
					path: `/${languageCode}`,
					value: label
				} ]
			).assertValidRequest().makeRequest();

			const context = {
				violation: 'item-label-description-duplicate',
				violation_context: {
					language: languageCode,
					conflicting_item_id: existingItemId
				}
			};

			assertValidError( response, 422, 'data-policy-violation', context );
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
		} );

		it( 'label-description-same-value', async () => {
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( testEnDescription ) ]
			)
				.assertValidRequest()
				.makeRequest();

			assertValidError(
				response,
				422,
				'data-policy-violation',
				{ violation: 'label-description-same-value', violation_context: { language: languageWithExistingLabel } }
			);
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
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

			assertValidError( response, 404, 'item-not-found' );
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

			assertValidError( response, 409, 'redirected-item', { redirect_target: redirectTarget } );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
		} );

		it( '"path" field target does not exist', async () => {
			const operation = { op: 'remove', path: '/path/does/not/exist' };

			const response = await newPatchItemLabelsRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest()
				.makeRequest();

			const context = { path: '/patch/0/path' };
			assertValidError( response, 409, 'patch-target-not-found', context );
			assert.strictEqual( response.body.message, 'Target not found on resource' );
		} );

		it( '"from" field target does not exist', async () => {
			const operation = { op: 'copy', from: '/path/does/not/exist', path: '/en' };

			const response = await newPatchItemLabelsRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest()
				.makeRequest();

			const context = { path: '/patch/0/from' };
			assertValidError( response, 409, 'patch-target-not-found', context );
			assert.strictEqual( response.body.message, 'Target not found on resource' );
		} );

		it( 'patch test condition failed', async () => {
			const operation = { op: 'test', path: '/en', value: 'potato' };
			const response = await newPatchItemLabelsRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 409, 'patch-test-failed', { path: '/patch/0', actual_value: testEnLabel } );
			assert.strictEqual( response.body.message, 'Test operation in the provided patch failed' );
		} );
	} );

	describe( '400 error response', () => {

		it( 'invalid item id', async () => {
			const itemId = testItemId.replace( 'Q', 'P' );
			const response = await newPatchItemLabelsRequestBuilder( itemId, [] )
				.assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'item_id' }
			);
		} );

		testValidatesPatch( ( patch ) => newPatchItemLabelsRequestBuilder( testItemId, patch ) );

		it( 'invalid edit tag', async () => {
			const response = await newPatchItemLabelsRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'tags', [ 'invalid tag' ] ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/tags/0' } );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newPatchItemLabelsRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/tags' } );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newPatchItemLabelsRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/bot' } );
		} );

		it( 'comment too long', async () => {
			const response = await newPatchItemLabelsRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'comment', 'x'.repeat( 501 ) )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'value-too-long', { path: '/comment', limit: 500 } );
			assert.strictEqual( response.body.message, 'The input value is too long' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newPatchItemLabelsRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/comment' } );
		} );
	} );
} );
