'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newSetPropertyLabelRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { formatTermEditSummary } = require( '../helpers/formatEditSummaries' );
const { makeEtag } = require( '../helpers/httpHelper' );

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

		// wait 1s before modifying labels to verify the last-modified timestamps are different
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
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
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
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
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
			const propertyId = 'X123';
			const response = await newSetPropertyLabelRequestBuilder( propertyId, 'en', 'test label' )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'invalid-property-id' );
			assert.include( response.body.message, propertyId );
		} );

		it( 'invalid language code', async () => {
			const invalidLanguageCode = '1e';
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, invalidLanguageCode, 'new label' )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'invalid-language-code' );
			assert.include( response.body.message, invalidLanguageCode );
		} );

		it( 'invalid label', async () => {
			const invalidLabel = 'tab characters \t not allowed';
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, 'en', invalidLabel )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'invalid-label' );
			assert.include( response.body.message, invalidLabel );
		} );

		it( 'label empty', async () => {
			const comment = 'Empty label';
			const emptyLabel = '';
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, 'en', emptyLabel )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'label-empty' );
			assert.strictEqual( response.body.message, 'Label must not be empty' );
		} );

		it( 'label too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLabelLength = 250;
			const labelTooLong = 'x'.repeat( maxLabelLength + 1 );
			const comment = 'Label too long';
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, 'en', labelTooLong )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'label-too-long' );
			assert.strictEqual(
				response.body.message,
				`Label must be no more than ${maxLabelLength} characters long`
			);
			assert.deepEqual(
				response.body.context,
				{ value: labelTooLong, 'character-limit': maxLabelLength }
			);

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
			const response = await newSetPropertyLabelRequestBuilder(
				testPropertyId,
				language,
				description
			).withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'label-description-same-value' );
			assert.strictEqual(
				response.body.message,
				`Label and description for language code '${language}' can not have the same value.`
			);
			assert.deepEqual( response.body.context, { language: language } );
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
			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'property-label-duplicate' );
			assert.strictEqual(
				response.body.message,
				`Property ${existingPropertyId} already has label '${label}' associated with ` +
				`language code '${languageCode}'`
			);
			assert.deepEqual(
				response.body.context,
				{
					language: languageCode,
					label: label,
					'matching-property-id': existingPropertyId
				}
			);
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, 'en', 'test label' )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, 'en', 'test label' )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid bot flag', async () => {
			const response = await newSetPropertyLabelRequestBuilder( testPropertyId, 'en', 'test label' )
				.withJsonBodyParam( 'bot', 'should be a boolean' )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );
	} );

} );
