'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newSetPropertyLabelRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { formatTermEditSummary } = require( '../helpers/formatEditSummaries' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );

describe( newSetPropertyLabelRequestBuilder().getRouteDescription(), () => {
	let testPropertyId;
	let originalLastModified;
	let originalRevisionId;

	function assertValidResponse( response, labelText ) {
		assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
		assert.strictEqual( response.body, labelText );
	}

	function assertValid200Response( response, labelText ) {
		expect( response ).to.have.status( 200 );
		assertValidResponse( response, labelText );
	}

	function assertValid201Response( response, labelText ) {
		expect( response ).to.have.status( 201 );
		assertValidResponse( response, labelText );
	}

	before( async () => {
		const createEntityResponse = await entityHelper.createEntity( 'property', {
			labels: { en: { language: 'en', value: `english label ${utils.uniq()}` } },
			datatype: 'string'
		} );
		testPropertyId = createEntityResponse.entity.id;

		const testPropertyCreationMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
		originalLastModified = new Date( testPropertyCreationMetadata.timestamp );
		originalRevisionId = testPropertyCreationMetadata.revid;

		// wait 1s before next test to ensure the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '20x success response ', () => {
		it( 'can add a label with edit metadata omitted', async () => {
			const languageCode = 'de';
			const newLabel = `neues deutsches Label ${utils.uniq()}`;
			const comment = 'omg look, i added a new label';
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, languageCode, newLabel )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, newLabel );

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
			assert.strictEqual(
				editMetadata.comment,
				formatTermEditSummary(
					'wbsetlabel',
					'add',
					languageCode,
					newLabel,
					comment
				)
			);
		} );

		it( 'can add a label with edit metadata provided', async () => {
			const languageCode = 'en-us';
			const newLabel = `new us-english label ${utils.uniq()}`;
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
			const comment = 'omg look, an edit i made';
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, languageCode, newLabel )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, newLabel );

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
			assert.deepEqual( editMetadata.tags, [ tag ] );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatTermEditSummary(
					'wbsetlabel',
					'add',
					languageCode,
					newLabel,
					comment
				)
			);
			assert.strictEqual( editMetadata.user, user.username );
		} );

		it( 'can replace a label with edit metadata omitted', async () => {
			const languageCode = 'en';
			const newLabel = `new label ${utils.uniq()}`;
			const comment = 'omg look, i replaced a new label';
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, languageCode, newLabel )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, newLabel );

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
			assert.strictEqual(
				editMetadata.comment,
				formatTermEditSummary(
					'wbsetlabel',
					'set',
					languageCode,
					newLabel,
					comment
				)
			);
		} );

		it( 'can replace a label with edit metadata provided', async () => {
			const languageCode = 'en';
			const newLabel = `new english label ${utils.uniq()}`;
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
			const comment = 'omg look, an edit i made';
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, languageCode, newLabel )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, newLabel );

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
			assert.deepEqual( editMetadata.tags, [ tag ] );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatTermEditSummary(
					'wbsetlabel',
					'set',
					languageCode,
					newLabel,
					comment
				)
			);
			assert.strictEqual( editMetadata.user, user.username );
		} );

		it( 'idempotency check: can set the same label twice', async () => {
			const languageCode = 'en';
			const newLabel = `new English Label ${utils.uniq()}`;
			const comment = 'omg look, i can set a new label';
			let response = await newSetPropertyLabelRequestBuilder( testPropertyId, languageCode, newLabel )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, newLabel );

			response = await newSetPropertyLabelRequestBuilder( testPropertyId, languageCode, newLabel )
				.withJsonBodyParam( 'comment', 'omg look, i can set the same label again' )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, newLabel );
		} );
	} );

	describe( '400 error response', () => {
		it( 'invalid property id', async () => {
			const response = await newSetPropertyLabelRequestBuilder( 'X123', 'en', 'test label' )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'property_id' }
			);
		} );

		it( 'invalid language code', async () => {
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, '1e', 'new label' )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'language_code' }
			);
		} );

		it( 'missing top-level field', async () => {
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, 'en', 'new label' )
				.withEmptyJsonBody()
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'missing-field' );
			assert.deepEqual( response.body.context, { path: '/', field: 'label' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( 'invalid label', async () => {
			const invalidLabel = 'tab characters \t not allowed';
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, 'en', invalidLabel )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/label' } );
			assert.strictEqual( response.body.message, "Invalid value at '/label'" );
		} );

		it( 'label empty', async () => {
			const comment = 'Empty label';
			const emptyLabel = '';
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, 'en', emptyLabel )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/label' } );
			assert.strictEqual( response.body.message, "Invalid value at '/label'" );
		} );

		it( 'label too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const limit = 250;
			const labelTooLong = 'x'.repeat( limit + 1 );
			const comment = 'Label too long';
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, 'en', labelTooLong )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'value-too-long', { path: '/label', limit: limit } );
			assert.strictEqual( response.body.message, 'The input value is too long' );
		} );

		it( 'label equals description', async () => {
			const language = 'en';
			const description = `some-description-${utils.uniq()}`;
			const createEntityResponse = await entityHelper.createEntity( 'property', {
				labels: [ { language: language, value: `some-label-${utils.uniq()}` } ],
				descriptions: [ { language: language, value: description } ],
				datatype: 'string'
			} );
			testPropertyId = createEntityResponse.entity.id;

			const comment = 'Label equals description';
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, language, description )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValidError(
				response,
				422,
				'data-policy-violation',
				{ violation: 'label-description-same-value', violation_context: { language } }
			);
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
		} );

		it( 'property with same label already exists', async () => {
			const languageCode = 'en';
			const label = `test-label-${utils.uniq()}`;
			const existingEntityResponse = await entityHelper.createEntity( 'property', {
				labels: [ { language: languageCode, value: label } ],
				datatype: 'string'
			} );
			const existingPropertyId = existingEntityResponse.entity.id;
			const createEntityResponse = await entityHelper.createEntity( 'property', {
				labels: [ { language: languageCode, value: `label-to-be-replaced-${utils.uniq()}` } ],
				datatype: 'string'
			} );
			testPropertyId = createEntityResponse.entity.id;

			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, languageCode, label )
				.assertValidRequest().makeRequest();

			const context = {
				violation: 'property-label-duplicate',
				violation_context: {
					language: languageCode,
					conflicting_property_id: existingPropertyId
				}
			};
			assertValidError( response, 422, 'data-policy-violation', context );
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
		} );

		it( 'comment too long', async () => {
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, 'en', 'test label' )
				.withJsonBodyParam( 'comment', 'x'.repeat( 501 ) )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'value-too-long', { path: '/comment', limit: 500 } );
			assert.strictEqual( response.body.message, 'The input value is too long' );
		} );

		it( 'invalid edit tag', async () => {
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, 'en', 'test label' )
				.withJsonBodyParam( 'tags', [ 'invalid tag' ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/tags/0' } );
		} );

		it( 'invalid bot flag', async () => {
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, 'en', 'test label' )
				.withJsonBodyParam( 'bot', 'should be a boolean' )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/bot' } );
		} );
	} );

	describe( '404 error response', () => {
		it( 'property not found', async () => {
			const propertyId = 'P999999';
			const response = await newSetPropertyLabelRequestBuilder( propertyId, 'en', 'test label' )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );
	} );
} );
