'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newPatchPropertyLabelsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { formatTermsEditSummary } = require( '../helpers/formatEditSummaries' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newPatchPropertyLabelsRequestBuilder().getRouteDescription(), () => {

	let testPropertyId;
	let testEnLabel;
	let originalLastModified;
	let originalRevisionId;
	const languageWithExistingLabel = 'en';

	before( async function () {
		testEnLabel = `some-label-${utils.uniq()}`;

		testPropertyId = ( await entityHelper.createEntity( 'property', {
			datatype: 'string',
			labels: [ { language: languageWithExistingLabel, value: testEnLabel } ]
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
		it( 'can add a label', async () => {
			const label = `neues deutsches label ${utils.uniq()}`;
			const response = await newPatchPropertyLabelsRequestBuilder(
				testPropertyId,
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
			const response = await newPatchPropertyLabelsRequestBuilder(
				testPropertyId,
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

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatTermsEditSummary( 'update-languages-short', 'ar', editSummary )
			);
		} );
	} );

	describe( '400 error response', () => {
		it( 'invalid property id', async () => {
			const response = await newPatchPropertyLabelsRequestBuilder( testPropertyId.replace( 'P', 'L' ), [] )
				.assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'property_id' }
			);
		} );

		testValidatesPatch( ( patch ) => newPatchPropertyLabelsRequestBuilder( testPropertyId, patch ) );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newPatchPropertyLabelsRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newPatchPropertyLabelsRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newPatchPropertyLabelsRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newPatchPropertyLabelsRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newPatchPropertyLabelsRequestBuilder( testPropertyId, [] )
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
			const response = await newPatchPropertyLabelsRequestBuilder(
				propertyId,
				[
					{
						op: 'replace',
						path: `/${languageWithExistingLabel}`,
						value: utils.uniq()
					}
				]
			).assertValidRequest().makeRequest();

			assertValidError( response, 404, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );
	} );

	describe( '409 error response', () => {
		it( '"path" field target does not exist', async () => {
			const operation = { op: 'remove', path: '/path/does/not/exist' };

			const response = await newPatchPropertyLabelsRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-target-not-found', { field: 'path', operation } );
			assert.include( response.body.message, operation.path );
		} );

		it( '"from" field target does not exist', async () => {
			const operation = { op: 'copy', from: '/path/does/not/exist', path: `/${languageWithExistingLabel}` };

			const response = await newPatchPropertyLabelsRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-target-not-found', { field: 'from', operation } );
			assert.include( response.body.message, operation.from );
		} );

		it( 'patch test condition failed', async () => {
			const operation = { op: 'test', path: `/${languageWithExistingLabel}`, value: 'instance of' };
			const response = await newPatchPropertyLabelsRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-test-failed', { operation, 'actual-value': testEnLabel } );
			assert.include( response.body.message, operation.path );
			assert.include( response.body.message, JSON.stringify( operation.value ) );
			assert.include( response.body.message, testEnLabel );
		} );
	} );

	describe( '422 error response', () => {
		const makeReplaceExistingLabelPatchOp = ( newLabel ) => ( {
			op: 'replace',
			path: `/${languageWithExistingLabel}`,
			value: newLabel
		} );

		it( 'invalid label', async () => {
			const language = languageWithExistingLabel;
			const invalid = 'tab characters \t not allowed';
			const response = await newPatchPropertyLabelsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingLabelPatchOp( invalid ) ]
			)
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 422, 'patched-label-invalid', { language, value: invalid } );
			assert.include( response.body.message, invalid );
			assert.include( response.body.message, `'${language}'` );
		} );

		it( 'invalid label type', async () => {
			const language = languageWithExistingLabel;
			const label = { object: 'not allowed' };
			const response = await newPatchPropertyLabelsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingLabelPatchOp( label ) ]
			)
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 422, 'patched-label-invalid', { language, value: JSON.stringify( label ) } );
			assert.include( response.body.message, JSON.stringify( label ) );
			assert.include( response.body.message, `'${languageWithExistingLabel}'` );
		} );

		it( 'empty label', async () => {
			const response = await newPatchPropertyLabelsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingLabelPatchOp( '' ) ]
			)
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 422, 'patched-label-empty', { language: languageWithExistingLabel } );
		} );

		it( 'label too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const tooLongLabel = 'x'.repeat( maxLength + 1 );
			const comment = 'Label too long';
			const response = await newPatchPropertyLabelsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingLabelPatchOp( tooLongLabel ) ]
			)
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			const context = { value: tooLongLabel, 'character-limit': maxLength, language: languageWithExistingLabel };
			assertValidError( response, 422, 'patched-label-too-long', context );
			assert.strictEqual(
				response.body.message,
				`Changed label for '${languageWithExistingLabel}' must not be more than ${maxLength} characters long`
			);
		} );

		it( 'invalid language code', async () => {
			const language = 'invalid-language-code';
			const response = await newPatchPropertyLabelsRequestBuilder(
				testPropertyId,
				[ {
					op: 'add',
					path: `/${language}`,
					value: 'potato'
				} ]
			)
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 422, 'patched-labels-invalid-language-code', { language } );
			assert.include( response.body.message, language );
		} );

		it( 'patched-property-label-description-same-value', async () => {
			const language = languageWithExistingLabel;
			const descriptionText = `description-text-${utils.uniq()}`;

			const createEntityResponse = await entityHelper.createEntity( 'property', {
				labels: [ { language: language, value: `label-text-${utils.uniq()}` } ],
				descriptions: [ { language: language, value: descriptionText } ],
				datatype: 'string'
			} );
			testPropertyId = createEntityResponse.entity.id;

			const response = await newPatchPropertyLabelsRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingLabelPatchOp( descriptionText ) ]
			)
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 422, 'patched-property-label-description-same-value', { language } );
			assert.strictEqual(
				response.body.message,
				`Label and description for language code ${language} can not have the same value.`
			);
		} );

		it( 'property with same label already exists', async () => {
			const label = `test-label-${utils.uniq()}`;

			const existingEntityResponse = await entityHelper.createEntity( 'property', {
				labels: [ { language: languageWithExistingLabel, value: label } ],
				datatype: 'string'
			} );
			const existingPropertyId = existingEntityResponse.entity.id;

			const createEntityResponse = await entityHelper.createEntity( 'property', {
				labels: [ { language: languageWithExistingLabel, value: `label-to-be-replaced-${utils.uniq()}` } ],
				datatype: 'string'
			} );
			testPropertyId = createEntityResponse.entity.id;

			const response = await newPatchPropertyLabelsRequestBuilder(
				testPropertyId,
				[ {
					op: 'replace',
					path: `/${languageWithExistingLabel}`,
					value: label
				} ]
			)
				.assertValidRequest().makeRequest();

			const context = { language: languageWithExistingLabel, label, 'matching-property-id': existingPropertyId };
			assertValidError( response, 422, 'patched-property-label-duplicate', context );

			assert.strictEqual(
				response.body.message,
				`Property ${existingPropertyId} already has label '${label}' associated with ` +
				`language code '${languageWithExistingLabel}'`
			);
		} );
	} );

} );
