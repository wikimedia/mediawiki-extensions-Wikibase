'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newPatchPropertyDescriptionsRequestBuilder,
	newGetPropertyDescriptionRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { formatTermsEditSummary } = require( '../helpers/formatEditSummaries' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );

describe( newPatchPropertyDescriptionsRequestBuilder().getRouteDescription(), () => {

	let testPropertyId;
	let originalLastModified;
	let originalRevisionId;
	const languageWithExistingDescription = 'en';
	const testEnLabel = `some-label-${utils.uniq()}`;

	function assertValid200Response( response ) {
		expect( response ).to.have.status( 200 );
		assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
	}

	before( async function () {
		testPropertyId = ( await entityHelper.createEntity( 'property', {
			datatype: 'string',
			labels: [ { language: 'en', value: testEnLabel } ],
			descriptions: [ { language: languageWithExistingDescription, value: `some-description-${utils.uniq()}` } ]
		} ) ).entity.id;

		const testPropertyCreationMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
		originalLastModified = new Date( testPropertyCreationMetadata.timestamp );
		originalRevisionId = testPropertyCreationMetadata.revid;

		// wait 1s before next test to ensure the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '200 OK', () => {
		it( 'can add a description', async () => {
			const description = `neues deutsches description ${utils.uniq()}`;
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ { op: 'add', path: '/de', value: description } ]
			).makeRequest();

			assertValid200Response( response );
			assert.strictEqual( response.body.de, description );
		} );

		it( 'can patch labels with edit metadata', async () => {
			const description = `new arabic label ${utils.uniq()}`;
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
			const comment = 'I made a patch';
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ { op: 'add', path: '/ar', value: description } ]
			).withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest().makeRequest();

			assertValid200Response( response );
			assert.strictEqual( response.body.ar, description );

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatTermsEditSummary( 'update-languages-short', 'ar', comment )
			);
		} );

		it( 'trims whitespace around the description', async () => {
			const description = `spacey ${utils.uniq()}`;
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ { op: 'add', path: '/de', value: `\t${description}  ` } ]
			).makeRequest();

			assertValid200Response( response );
			assert.strictEqual( response.body.de, description );
		} );

		it( 'allows content-type application/json-patch+json', async () => {
			const expectedValue = `new arabic description ${utils.uniq()}`;
			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [
				{
					op: 'replace',
					path: '/ar',
					value: expectedValue
				}
			] )
				.withHeader( 'content-type', 'application/json-patch+json' )
				.assertValidRequest().makeRequest();

			assertValid200Response( response );
			assert.strictEqual( response.body.ar, expectedValue );
		} );

		it( 'allows content-type application/json', async () => {
			const expectedValue = `new arabic description ${utils.uniq()}`;
			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [
				{
					op: 'replace',
					path: '/ar',
					value: expectedValue
				}
			] )
				.withHeader( 'content-type', 'application/json' )
				.assertValidRequest().makeRequest();

			assertValid200Response( response );
			assert.strictEqual( response.body.ar, expectedValue );
		} );
	} );

	describe( '400 error response', () => {
		it( 'invalid property id', async () => {
			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId.replace( 'P', 'Q' ), [] )
				.assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'property_id' }
			);
		} );

		testValidatesPatch( ( patch ) => newPatchPropertyDescriptionsRequestBuilder( testPropertyId, patch ) );

		it( 'invalid edit tag', async () => {
			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'tags', [ 'invalid tag' ] ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/tags/0' } );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/tags' } );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/bot' } );
		} );

		it( 'comment too long', async () => {
			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'comment', 'x'.repeat( 501 ) )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'value-too-long', { path: '/comment', limit: 500 } );
			assert.strictEqual( response.body.message, 'The input value is too long' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/comment' } );
		} );
	} );

	describe( '404 error response', () => {
		it( 'property not found', async () => {
			const propertyId = 'P99999';
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				propertyId,
				[ { op: 'replace', path: '/en', value: utils.uniq() } ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 404, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );
	} );

	describe( '409 error response', () => {
		it( '"path" field target does not exist', async () => {
			const operation = { op: 'remove', path: '/path/does/not/exist' };

			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			const context = { path: '/patch/0/path' };
			assertValidError( response, 409, 'patch-target-not-found', context );
			assert.strictEqual( response.body.message, 'Target not found on resource' );
		} );

		it( '"from" field target does not exist', async () => {
			const operation = { op: 'copy', from: '/path/does/not/exist', path: '/en' };

			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			const context = { path: '/patch/0/from' };
			assertValidError( response, 409, 'patch-target-not-found', context );
			assert.strictEqual( response.body.message, 'Target not found on resource' );
		} );

		it( 'patch test condition failed', async () => {
			const operation = { op: 'test', path: '/en', value: 'incorrect' };
			const enDescription = ( await newGetPropertyDescriptionRequestBuilder( testPropertyId, 'en' ).makeRequest() ).body;
			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-test-failed', { path: '/patch/0', actual_value: enDescription } );
			assert.strictEqual( response.body.message, 'Test operation in the provided patch failed' );
		} );
	} );

	describe( '422 error response', () => {
		const makeReplaceExistingDescriptionPatchOperation = ( newDescription ) => ( {
			op: 'replace',
			path: '/en',
			value: newDescription
		} );

		it( 'invalid description', async () => {
			const invalidDescription = 'tab characters \t not allowed';
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( invalidDescription ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/${languageWithExistingDescription}`, value: invalidDescription };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'invalid description type', async () => {
			const invalidDescription = { object: 'not allowed' };
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( invalidDescription ) ]
			).assertValidRequest().makeRequest();

			assertValidError(
				response,
				422,
				'patch-result-invalid-value',
				{ path: `/${languageWithExistingDescription}`, value: invalidDescription }
			);
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'empty description', async () => {
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( '' ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/${languageWithExistingDescription}`, value: '' };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
		} );

		it( 'empty description after trimming whitespace in the input', async () => {
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( ' \t ' ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/${languageWithExistingDescription}`, value: '' };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
		} );

		it( 'description too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( 'x'.repeat( maxLength + 1 ) ) ]
			).assertValidRequest().makeRequest();

			const context = { path: '/en', limit: maxLength };
			assertValidError( response, 422, 'patch-result-value-too-long', context );
			assert.strictEqual( response.body.message, 'Patched value is too long' );
		} );

		it( 'invalid language code', async () => {
			const invalidLanguage = 'invalid-language-code';
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ { op: 'add', path: `/${invalidLanguage}`, value: 'potato' } ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patch-result-invalid-key', { path: '', key: invalidLanguage } );
		} );

		it( 'label-description-same-value', async () => {
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( testEnLabel ) ]
			).assertValidRequest().makeRequest();

			assertValidError(
				response,
				422,
				'data-policy-violation',
				{ violation: 'label-description-same-value', violation_context: { language: 'en' } }
			);
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
		} );
	} );
} );
