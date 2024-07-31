'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newSetItemDescriptionRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { formatTermEditSummary } = require( '../helpers/formatEditSummaries' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/botUser' );

describe( newSetItemDescriptionRequestBuilder().getRouteDescription(), () => {
	let testItemId;
	let testEnLabel;
	let originalLastModified;
	let originalRevisionId;

	before( async () => {
		testEnLabel = `some-label-${utils.uniq()}`;
		const createEntityResponse = await entityHelper.createEntity( 'item', {
			labels: [ { language: 'en', value: testEnLabel } ],
			descriptions: [ { language: 'en', value: `some-description-${utils.uniq()}` } ]
		} );
		testItemId = createEntityResponse.entity.id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		// wait 1s before next test to ensure the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	function assertValidResponse( response, description ) {
		assert.strictEqual( response.body, description );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
	}

	function assertValid200Response( response, description ) {
		expect( response ).to.have.status( 200 );
		assertValidResponse( response, description );
	}

	function assertValid201Response( response, description ) {
		expect( response ).to.have.status( 201 );
		assertValidResponse( response, description );
	}

	describe( '20x success', () => {
		it( 'can add a description with edit metadata omitted', async () => {
			const description = `neue Beschreibung ${utils.uniq()}`;
			const languageCode = 'de';
			const response = await newSetItemDescriptionRequestBuilder( testItemId, languageCode, description )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, description );
		} );

		it( 'can add a description with edit metadata provided', async () => {
			const description = `new US English description ${utils.uniq()}`;
			const languageCode = 'en-us';
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
			const comment = 'omg i added a description!!1';

			const response = await newSetItemDescriptionRequestBuilder( testItemId, languageCode, description )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, description );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.deepEqual( editMetadata.tags, [ tag ] );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatTermEditSummary(
					'wbsetdescription',
					'add',
					languageCode,
					description,
					comment
				)
			);
		} );

		it( 'can replace a description with edit metadata omitted', async () => {
			const description = `new description ${utils.uniq()}`;
			const languageCode = 'en';
			const response = await newSetItemDescriptionRequestBuilder( testItemId, languageCode, description )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, description );
		} );

		it( 'can replace a description with edit metadata provided', async () => {
			const description = `new description ${utils.uniq()}`;
			const languageCode = 'en';
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
			const comment = 'omg i replaced a description!!1';

			const response = await newSetItemDescriptionRequestBuilder( testItemId, languageCode, description )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, description );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.deepEqual( editMetadata.tags, [ tag ] );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatTermEditSummary(
					'wbsetdescription',
					'set',
					languageCode,
					description,
					comment
				)
			);
		} );

		it( 'idempotency check: can set the same description twice', async () => {
			const languageCode = 'en';
			const newDescription = `new English description ${utils.uniq()}`;
			const comment = 'omg look, i can set a new description';
			let response = await newSetItemDescriptionRequestBuilder( testItemId, languageCode, newDescription )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, newDescription );

			response = await newSetItemDescriptionRequestBuilder( testItemId, languageCode, newDescription )
				.withJsonBodyParam( 'comment', 'omg look, i can set the same description again' )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, newDescription );
		} );

	} );

	describe( '400 error response', () => {

		it( 'invalid item ID', async () => {
			const invalidItemId = 'X11';
			const response = await newSetItemDescriptionRequestBuilder(
				invalidItemId, 'en', 'item description' )
				.assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'item_id' }
			);
		} );

		[ 'xyz', 'mul' ].forEach( ( invalidLanguage ) => {
			it( `invalid language code: "${invalidLanguage}"`, async () => {
				const response = await newSetItemDescriptionRequestBuilder( testItemId, invalidLanguage, 'description' )
					.withHeader( 'X-Wikibase-Ci-Enable-Mul', 'true' ).assertValidRequest().makeRequest();

				assertValidError(
					response,
					400,
					'invalid-path-parameter',
					{ parameter: 'language_code' }
				);
			} );
		} );

		it( 'missing top-level field', async () => {
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', 'description' )
				.withEmptyJsonBody()
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'missing-field' );
			assert.deepEqual( response.body.context, { path: '/', field: 'description' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( 'invalid description', async () => {
			const invalidDescription = 'tab characters \t not allowed';
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', invalidDescription )
				.assertValidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/description' } );
			assert.strictEqual( response.body.message, "Invalid value at '/description'" );
		} );

		it( 'description empty', async () => {
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', '' )
				.assertValidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/description' } );
			assert.strictEqual( response.body.message, "Invalid value at '/description'" );
		} );

		it( 'description too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const limit = 250;
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', 'a'.repeat( limit + 1 ) )
				.assertValidRequest().makeRequest();

			assertValidError( response, 400, 'value-too-long', { path: '/description', limit: limit } );
			assert.strictEqual( response.body.message, 'The input value is too long' );
		} );

		it( 'description is the same as the label', async () => {
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', testEnLabel )
				.assertValidRequest().makeRequest();

			assertValidError( response, 400, 'label-description-same-value', { language: 'en' } );
			assert.include( response.body.message, 'en' );
		} );

		it( 'item with same label and description already exists', async () => {
			const language = 'en';
			const description = `some-description-${utils.uniq()}`;
			const createEntityResponse = await entityHelper.createEntity( 'item', {
				labels: [ { language, value: testEnLabel } ],
				descriptions: [ { language, value: description } ]
			} );
			const conflictingItemId = createEntityResponse.entity.id;

			const response = await newSetItemDescriptionRequestBuilder( testItemId, language, description )
				.assertValidRequest().makeRequest();

			const context = {
				violation: 'item-label-description-duplicate',
				violation_context: {
					language: language,
					conflicting_item_id: conflictingItemId
				}
			};

			assertValidError( response, 422, 'data-policy-violation', context );
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
		} );

		it( 'invalid edit tag', async () => {
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', 'item description' )
				.withJsonBodyParam( 'tags', [ 'invalid tag' ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/tags/0' } );
		} );

		it( 'invalid comment type', async () => {
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', 'description' )
				.withJsonBodyParam( 'comment', 1234 )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/comment' } );
		} );

		it( 'comment too long', async () => {
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', 'item description' )
				.withJsonBodyParam( 'comment', 'x'.repeat( 501 ) )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'value-too-long', { path: '/comment', limit: 500 } );
			assert.strictEqual( response.body.message, 'The input value is too long' );
		} );

	} );

	describe( '404 error response', () => {
		it( 'item not found', async () => {
			const itemId = 'Q999999';
			const response = await newSetItemDescriptionRequestBuilder( itemId, 'en', 'test description' )
				.assertValidRequest().makeRequest();

			assertValidError( response, 404, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );
	} );

	describe( '409 error response', () => {
		it( 'item is a redirect', async () => {
			const redirectTarget = testItemId;
			const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

			const response = await newSetItemDescriptionRequestBuilder( redirectSource, 'en', 'test description' )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'redirected-item', { redirect_target: redirectTarget } );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
		} );

		it( 'item is a redirect and item label+description collision', async () => {
			const redirectTarget = testItemId;
			const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );
			const description = `some-description-${utils.uniq()}`;
			await entityHelper.createEntity( 'item', {
				labels: [ { language: 'en', value: testEnLabel } ],
				descriptions: [ { language: 'en', value: description } ]
			} );

			const response = await newSetItemDescriptionRequestBuilder( redirectSource, 'en', description )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'redirected-item', { redirect_target: redirectTarget } );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
		} );
	} );
} );
