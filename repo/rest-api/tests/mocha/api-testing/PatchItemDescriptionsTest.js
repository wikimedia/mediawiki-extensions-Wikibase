'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const { newPatchItemDescriptionsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { formatTermsEditSummary } = require( '../helpers/formatEditSummaries' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );

describe( newPatchItemDescriptionsRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let testLabel;
	let testDescription;
	let originalLastModified;
	let originalRevisionId;
	const testLanguage = 'en';

	before( async function () {
		testLabel = `English Label ${utils.uniq()}`;
		testDescription = `English Description ${utils.uniq()}`;
		testItemId = ( await entityHelper.createEntity( 'item', {
			labels: [ { language: testLanguage, value: testLabel } ],
			descriptions: [ { language: testLanguage, value: testDescription } ]
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
		it( 'can add a description', async () => {
			const description = `Neues Deutsches Beschreibung ${utils.uniq()}`;
			const response = await newPatchItemDescriptionsRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/de', value: description } ]
			).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.de, description );
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
			assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
			assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
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
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
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
			assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
			assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatTermsEditSummary( 'update-languages-short', 'ar', comment )
			);
		} );
	} );

	describe( '400 error response', () => {
		it( 'invalid item id', async () => {
			const itemId = testItemId.replace( 'Q', 'P' );
			const response = await newPatchItemDescriptionsRequestBuilder( itemId, [] )
				.assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'item_id' }
			);
		} );

		testValidatesPatch( ( patch ) => newPatchItemDescriptionsRequestBuilder( testItemId, patch ) );
	} );

	describe( '404 error response', () => {
		it( 'item not found', async () => {
			const itemId = 'Q999999';
			const response = await newPatchItemDescriptionsRequestBuilder(
				itemId,
				[ { op: 'replace', path: '/en', value: utils.uniq() } ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'item' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );
	} );

	describe( '409 error response', () => {
		it( 'item is a redirect', async () => {
			const redirectTarget = testItemId;
			const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

			const response = await newPatchItemDescriptionsRequestBuilder(
				redirectSource,
				[ { op: 'replace', path: '/en', value: utils.uniq() } ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 409, 'redirected-item', { redirect_target: redirectTarget } );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
		} );

		it( '"path" field target does not exist', async () => {
			const operation = { op: 'remove', path: '/path/does/not/exist' };

			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest()
				.makeRequest();

			const context = { path: '/patch/0/path' };
			assertValidError( response, 409, 'patch-target-not-found', context );
			assert.strictEqual( response.body.message, 'Target not found on resource' );
		} );

		it( '"from" field target does not exist', async () => {
			const operation = { op: 'copy', from: '/path/does/not/exist', path: '/en' };

			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest()
				.makeRequest();

			const context = { path: '/patch/0/from' };
			assertValidError( response, 409, 'patch-target-not-found', context );
			assert.strictEqual( response.body.message, 'Target not found on resource' );
		} );

		it( 'patch test condition failed', async () => {
			const operation = { op: 'test', path: '/en', value: 'potato' };
			const response = await newPatchItemDescriptionsRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 409, 'patch-test-failed', { path: '/patch/0', actual_value: testDescription } );
			assert.strictEqual( response.body.message, 'Test operation in the provided patch failed' );
		} );
	} );

	describe( '422 error response', () => {
		const makeReplaceExistingDescriptionPatchOperation = ( newDescription ) => ( {
			op: 'replace',
			path: `/${testLanguage}`,
			value: newDescription
		} );

		it( 'invalid descriptions type', async () => {
			const invalidDescriptions = '';
			const response = await newPatchItemDescriptionsRequestBuilder(
				testItemId,
				[ { op: 'replace', path: '', value: invalidDescriptions } ]
			).assertValidRequest().makeRequest();

			const context = { path: '', value: invalidDescriptions };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'descriptions is not an object', async () => {
			const invalidDescriptions = [ 'list, not an object' ];
			const response = await newPatchItemDescriptionsRequestBuilder(
				testItemId,
				[ { op: 'replace', path: '', value: invalidDescriptions } ]
			).assertValidRequest().makeRequest();

			const context = { path: '', value: invalidDescriptions };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'invalid description', async () => {
			const invalidDescription = 'tab characters \t not allowed';
			const response = await newPatchItemDescriptionsRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( invalidDescription ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/${testLanguage}`, value: invalidDescription };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'invalid description type', async () => {
			const invalidDescription = { object: 'not allowed' };
			const response = await newPatchItemDescriptionsRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( invalidDescription ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/${testLanguage}`, value: invalidDescription };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'empty description', async () => {
			const response = await newPatchItemDescriptionsRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( '' ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/${testLanguage}`, value: '' };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
		} );

		it( 'empty description after trimming whitespace in the input', async () => {
			const response = await newPatchItemDescriptionsRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( ' \t ' ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/${testLanguage}`, value: '' };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
		} );

		it( 'description too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const response = await newPatchItemDescriptionsRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( 'x'.repeat( maxLength + 1 ) ) ]
			).assertValidRequest().makeRequest();

			const context = { path: '/en', limit: maxLength };
			assertValidError( response, 422, 'patch-result-value-too-long', context );
			assert.strictEqual( response.body.message, 'Patched value is too long' );
		} );

		[ 'invalid-language-code', 'mul' ].forEach( ( invalidLanguage ) => {
			it( `invalid language code: "${invalidLanguage}"`, async () => {
				const response = await newPatchItemDescriptionsRequestBuilder(
					testItemId,
					[ { op: 'add', path: `/${invalidLanguage}`, value: 'potato' } ]
				).withConfigOverride( 'wgWBRepoSettings', { tmpEnableMulLanguageCode: true } ).assertValidRequest().makeRequest();

				assertValidError( response, 422, 'patch-result-invalid-key', { path: '', key: invalidLanguage } );
			} );
		} );

		it( 'patched label and description already exists in a different item', async () => {
			const label = `test-label-${utils.uniq()}`;
			const description = `test-description-${utils.uniq()}`;
			const existingItemId = ( await entityHelper.createEntity( 'item', {
				labels: [ { language: testLanguage, value: label } ],
				descriptions: [ { language: testLanguage, value: description } ]
			} ) ).entity.id;
			const itemIdToBePatched = ( await entityHelper.createEntity( 'item', {
				labels: [ { language: testLanguage, value: label } ],
				descriptions: [ { language: testLanguage, value: `description-to-be-replaced-${utils.uniq()}` } ]
			} ) ).entity.id;

			const response = await newPatchItemDescriptionsRequestBuilder(
				itemIdToBePatched,
				[ { op: 'replace', path: `/${testLanguage}`, value: description } ]
			).assertValidRequest().makeRequest();

			const context = {
				violation: 'item-label-description-duplicate',
				violation_context: {
					language: testLanguage,
					conflicting_item_id: existingItemId
				}
			};

			assertValidError( response, 422, 'data-policy-violation', context );
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
		} );

		it( 'label-description-same-value', async () => {
			const response = await newPatchItemDescriptionsRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( testLabel ) ]
			).assertValidRequest().makeRequest();

			assertValidError(
				response,
				422,
				'data-policy-violation',
				{ violation: 'label-description-same-value', violation_context: { language: testLanguage } }
			);
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
		} );
	} );

} );
