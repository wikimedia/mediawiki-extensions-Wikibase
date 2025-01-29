'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newSetItemLabelRequestBuilder, newCreateItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { formatTermEditSummary } = require( '../helpers/formatEditSummaries' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );

describe( newSetItemLabelRequestBuilder().getRouteDescription(), () => {
	let testItemId;
	let testEnDescription;
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
		testEnDescription = `english description ${utils.uniq()}`;
		const createEntityResponse = await newCreateItemRequestBuilder( {
			labels: { en: `english label ${utils.uniq()}`, fr: `étiquette française ${utils.uniq()}` },
			descriptions: { en: testEnDescription } }
		).makeRequest();
		testItemId = createEntityResponse.body.id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

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
			const response = await newSetItemLabelRequestBuilder( testItemId, languageCode, newLabel )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, newLabel );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
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

		it( 'can replace a label with edit metadata provided', async () => {
			const languageCode = 'en';
			const newLabel = `new english label ${utils.uniq()}`;
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
			const comment = 'omg look, an edit i made';
			const response = await newSetItemLabelRequestBuilder( testItemId, languageCode, newLabel )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, newLabel );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
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

		it( 'can add a "mul" label', async () => {
			const languageCode = 'mul';
			const newLabel = `new mul label ${utils.uniq()}`;
			const response = await newSetItemLabelRequestBuilder( testItemId, languageCode, newLabel )
				.withConfigOverride( 'wgWBRepoSettings', { enableMulLanguageCode: true } )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, newLabel );
		} );
	} );

	it( 'idempotency check: can set the same label twice', async () => {
		const languageCode = 'en';
		const newLabel = `new English Label ${utils.uniq()}`;
		const comment = 'omg look, i can set a new label';
		let response = await newSetItemLabelRequestBuilder( testItemId, languageCode, newLabel )
			.withJsonBodyParam( 'comment', comment )
			.assertValidRequest()
			.makeRequest();

		assertValid200Response( response, newLabel );

		response = await newSetItemLabelRequestBuilder( testItemId, languageCode, newLabel )
			.withJsonBodyParam( 'comment', 'omg look, i can set the same label again' )
			.assertValidRequest()
			.makeRequest();

		assertValid200Response( response, newLabel );
	} );

	describe( '400 error response', () => {
		it( 'invalid item id', async () => {
			const itemId = 'X123';
			const response = await newSetItemLabelRequestBuilder( itemId, 'en', 'test label' )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'item_id' }
			);
		} );

		it( 'invalid language code', async () => {
			const response = await newSetItemLabelRequestBuilder( testItemId, '1e', 'test label' )
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
			const response = await newSetItemLabelRequestBuilder( testItemId, 'en', 'new label' )
				.withEmptyJsonBody()
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'missing-field' );
			assert.deepEqual( response.body.context, { path: '', field: 'label' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( 'invalid label', async () => {
			const invalidLabel = 'tab characters \t not allowed';
			const response = await newSetItemLabelRequestBuilder( testItemId, 'en', invalidLabel )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/label' } );
			assert.strictEqual( response.body.message, "Invalid value at '/label'" );
		} );

		it( 'label empty', async () => {
			const comment = 'Empty label';
			const emptyLabel = '';
			const response = await newSetItemLabelRequestBuilder( testItemId, 'en', emptyLabel )
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
			const response = await newSetItemLabelRequestBuilder( testItemId, 'en', labelTooLong )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'value-too-long', { path: '/label', limit: limit } );
			assert.strictEqual( response.body.message, 'The input value is too long' );
		} );

		it( 'label equals description', async () => {
			const language = 'en';
			const description = `some-description-${utils.uniq()}`;
			const createEntityResponse = await newCreateItemRequestBuilder( {
				labels: { [ language ]: `some-label-${utils.uniq()}` },
				descriptions: { [ language ]: description }
			} ).makeRequest();
			testItemId = createEntityResponse.body.id;

			const comment = 'Label equals description';
			const response = await newSetItemLabelRequestBuilder( testItemId, language, description )
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

	} );
	it( 'item with same label and description already exists', async () => {
		const language = 'en';
		const label = `test-label-${utils.uniq()}`;
		const description = `test-description-${utils.uniq()}`;
		const existingEntityResponse = await newCreateItemRequestBuilder( {
			labels: { [ language ]: label }, descriptions: { [ language ]: description } }
		).makeRequest();
		const existingItemId = existingEntityResponse.body.id;
		const createEntityResponse = await newCreateItemRequestBuilder(
			{ labels: { [ language ]: `label-to-be-replaced-${utils.uniq()}` }, descriptions: { [ language ]: description } }
		).makeRequest();
		testItemId = createEntityResponse.body.id;

		const response = await newSetItemLabelRequestBuilder( testItemId, language, label )
			.assertValidRequest().makeRequest();

		const context = {
			violation: 'item-label-description-duplicate',
			violation_context: {
				language: language,
				conflicting_item_id: existingItemId
			}
		};

		assertValidError( response, 422, 'data-policy-violation', context );
		assert.strictEqual( response.body.message, 'Edit violates data policy' );
	} );

	describe( '404 error response', () => {
		it( 'item not found', async () => {
			const itemId = 'Q999999';
			const response = await newSetItemLabelRequestBuilder( itemId, 'en', 'test label' )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'item' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );
	} );

	describe( '409 error response', () => {
		it( 'item is a redirect', async () => {
			const redirectTarget = testItemId;
			const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

			const response = await newSetItemLabelRequestBuilder( redirectSource, 'en', 'test label' )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 409, 'redirected-item', { redirect_target: redirectTarget } );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
		} );

		it( 'item is a redirect and label equals description', async () => {
			const redirectTarget = testItemId;
			const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

			const response = await newSetItemLabelRequestBuilder( redirectSource, 'en', testEnDescription )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 409, 'redirected-item', { redirect_target: redirectTarget } );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
		} );
	} );
} );
