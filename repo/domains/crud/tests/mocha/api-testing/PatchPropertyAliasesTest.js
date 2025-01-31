'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newPatchPropertyAliasesRequestBuilder,
	newGetPropertyAliasesInLanguageRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const { formatTermsEditSummary } = require( '../helpers/formatEditSummaries' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );

describe( newPatchPropertyAliasesRequestBuilder().getRouteDescription(), () => {
	let testPropertyId;
	let originalLastModified;
	let originalRevisionId;
	const languageWithExistingAlias = 'en';
	const existingEnAlias = `en-alias-${utils.uniq()}`;

	function assertValid200Response( response ) {
		expect( response ).to.have.status( 200 );
		assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
	}

	before( async () => {
		const aliases = {};
		aliases[ languageWithExistingAlias ] = [ existingEnAlias ];
		testPropertyId = ( await newCreatePropertyRequestBuilder( { data_type: 'string', aliases } ).makeRequest() ).body.id;

		const testPropertyCreationMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
		originalLastModified = new Date( testPropertyCreationMetadata.timestamp );
		originalRevisionId = testPropertyCreationMetadata.revid;

		// wait 1s before next test to ensure the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '200 OK', () => {
		it( 'can patch aliases', async () => {
			const newDeAlias = `de-alias-${utils.uniq()}`;
			const newEnAlias = `en-alias-${utils.uniq()}`;
			const newEnAliasWithTrailingWhitespace = `\t  ${newEnAlias}  `;
			const response = await newPatchPropertyAliasesRequestBuilder(
				testPropertyId,
				[
					{ op: 'add', path: '/de', value: [ newDeAlias ] },
					{ op: 'add', path: '/en/-', value: newEnAliasWithTrailingWhitespace },
					{ op: 'add', path: '/en/-', value: existingEnAlias }
				]
			).makeRequest();

			assertValid200Response( response );
			assert.deepEqual( response.body.de, [ newDeAlias ] );
			assert.deepEqual( response.body.en, [ existingEnAlias, newEnAlias ] );
		} );

		it( 'can patch aliases providing edit metadata', async () => {
			const newDeAlias = `de-alias-${utils.uniq()}`;
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
			const editSummary = 'I made a patch';
			const response = await newPatchPropertyAliasesRequestBuilder(
				testPropertyId,
				[ { op: 'add', path: '/de', value: [ newDeAlias ] } ]
			).withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', editSummary )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response );

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatTermsEditSummary( 'update-languages-short', 'de', editSummary )
			);
		} );
	} );

	describe( '400 Bad Request', () => {
		it( 'property ID is invalid', async () => {
			const response = await newPatchPropertyAliasesRequestBuilder( 'X123', [] )
				.assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'property_id' }
			);
		} );

		testValidatesPatch( ( patch ) => newPatchPropertyAliasesRequestBuilder( testPropertyId, patch ) );
	} );

	describe( '422 Unprocessable Content', () => {
		it( 'empty alias', async () => {
			const language = 'de';
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/${language}`, value: [ '' ] }
			] ).assertValidRequest().makeRequest();

			const context = { path: `/${language}/0`, value: '' };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'alias too long', async () => {
			const language = 'de';
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/${language}`, value: [ 'x'.repeat( maxLength + 1 ) ] }
			] ).assertValidRequest().makeRequest();

			const context = { path: `/${language}/0`, limit: maxLength };
			assertValidError( response, 422, 'patch-result-value-too-long', context );
			assert.strictEqual( response.body.message, 'Patched value is too long' );
		} );

		it( 'alias contains invalid characters', async () => {
			const language = 'en';
			const invalidAlias = 'tab\t tab\t tab';
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/${language}`, value: [ invalidAlias ] }
			] ).assertValidRequest().makeRequest();

			assertValidError(
				response,
				422,
				'patch-result-invalid-value',
				{ path: `/${language}/0`, value: invalidAlias }
			);
		} );

		it( 'invalid language code', async () => {
			const invalidLanguage = 'not-a-valid-language';
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/${invalidLanguage}`, value: [ 'alias' ] }
			] ).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patch-result-invalid-key', { path: '', key: invalidLanguage } );
		} );
	} );

	it( '404 if the property does not exist', async () => {
		const propertyId = 'P999999999';
		const response = await newPatchPropertyAliasesRequestBuilder( propertyId, [] )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'property' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	describe( '409', () => {
		it( '"path" field target does not exist', async () => {
			const operation = { op: 'remove', path: '/path/does/not/exist' };
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest()
				.makeRequest();

			const context = { path: '/patch/0/path' };
			assertValidError( response, 409, 'patch-target-not-found', context );
			assert.strictEqual( response.body.message, 'Target not found on resource' );
		} );

		it( '"from" field target does not exist', async () => {
			const operation = { op: 'copy', from: '/path/does/not/exist', path: '/en' };
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest()
				.makeRequest();

			const context = { path: '/patch/0/from' };
			assertValidError( response, 409, 'patch-target-not-found', context );
			assert.strictEqual( response.body.message, 'Target not found on resource' );
		} );

		it( 'patch test condition failed', async () => {
			const operation = { op: 'test', path: '/en/0', value: 'potato' };
			const enAliases = ( await newGetPropertyAliasesInLanguageRequestBuilder( testPropertyId, 'en' )
				.makeRequest() ).body;

			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 409, 'patch-test-failed', { path: '/patch/0', actual_value: enAliases[ 0 ] } );
			assert.strictEqual( response.body.message, 'Test operation in the provided patch failed' );
		} );
	} );

} );
