'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newPatchPropertyDescriptionsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { formatTermsEditSummary } = require( '../helpers/formatEditSummaries' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newPatchPropertyDescriptionsRequestBuilder().getRouteDescription(), () => {

	let testPropertyId;
	let originalLastModified;
	let originalRevisionId;
	const languageWithExistingDescription = 'en';
	const testEnDescription = `some-description-${utils.uniq()}`;
	const testEnLabel = `some-label-${utils.uniq()}`;

	before( async function () {
		testPropertyId = ( await entityHelper.createEntity( 'property', {
			datatype: 'string',
			labels: [ { language: 'en', value: testEnLabel } ],
			descriptions: [ { language: languageWithExistingDescription, value: testEnDescription } ]
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

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.de, description );
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
			assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
			assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
		} );

		it( 'can patch labels with edit metadata', async () => {
			const description = `new arabic label ${utils.uniq()}`;
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const comment = 'I made a patch';
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ { op: 'add', path: '/ar', value: description } ]
			).withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest().makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.ar, description );
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
			assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
			assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );

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

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.de, description );
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
			const invalidEditTag = 'invalid tag';
			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'comment' );
			assert.strictEqual( response.body.expectedType, 'string' );
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

			assertValidError( response, 409, 'patch-target-not-found', { field: 'path', operation } );
			assert.include( response.body.message, operation.path );
		} );

		it( '"from" field target does not exist', async () => {
			const operation = { op: 'copy', from: '/path/does/not/exist', path: '/en' };

			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-target-not-found', { field: 'from', operation } );
			assert.include( response.body.message, operation.from );
		} );

		it( 'patch test condition failed', async () => {
			const operation = { op: 'test', path: '/en', value: 'incorrect' };
			const response = await newPatchPropertyDescriptionsRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-test-failed', { operation, 'actual-value': testEnDescription } );
			assert.include( response.body.message, operation.path );
			assert.include( response.body.message, JSON.stringify( operation.value ) );
			assert.include( response.body.message, testEnDescription );
		} );
	} );

	describe( '422 error response', () => {
		const makeReplaceExistingDescriptionPatchOperation = ( newDescription ) => ( {
			op: 'replace',
			path: '/en',
			value: newDescription
		} );

		it( 'invalid description', async () => {
			const language = 'en';
			const invalidDescription = 'tab characters \t not allowed';
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( invalidDescription ) ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-description-invalid', { language, value: invalidDescription } );
			assert.include( response.body.message, invalidDescription );
			assert.include( response.body.message, `'${language}'` );
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
				'patched-description-invalid',
				{ language: 'en', value: JSON.stringify( invalidDescription ) }
			);
			assert.include( response.body.message, JSON.stringify( invalidDescription ) );
			assert.include( response.body.message, "'en'" );
		} );

		it( 'empty description', async () => {
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( '' ) ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-description-empty', { language: 'en' } );
		} );

		it( 'empty description after trimming whitespace in the input', async () => {
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( ' \t ' ) ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-description-empty', { language: 'en' } );
		} );

		it( 'description too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const tooLongDescription = 'x'.repeat( maxLength + 1 );
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( tooLongDescription ) ]
			).assertValidRequest().makeRequest();

			assertValidError(
				response,
				422,
				'patched-description-too-long',
				{ value: tooLongDescription, 'character-limit': maxLength, language: 'en' }
			);
			assert.strictEqual(
				response.body.message,
				`Changed description for 'en' must not be more than ${maxLength} characters long`
			);
		} );

		it( 'invalid language code', async () => {
			const language = 'invalid-language-code';
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ { op: 'add', path: `/${language}`, value: 'potato' } ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-descriptions-invalid-language-code', { language } );
			assert.include( response.body.message, language );
		} );

		it( 'patched-property-label-description-same-value', async () => {
			const response = await newPatchPropertyDescriptionsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( testEnLabel ) ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-property-label-description-same-value', { language: 'en' } );
			assert.strictEqual(
				response.body.message,
				'Label and description for language code en can not have the same value.'
			);
		} );
	} );
} );
