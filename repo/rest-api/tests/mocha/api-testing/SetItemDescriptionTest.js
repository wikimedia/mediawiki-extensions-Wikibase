'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newSetItemDescriptionRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { formatTermEditSummary } = require( '../helpers/formatEditSummaries' );
const { assertValidError } = require( '../helpers/responseValidator' );

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
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
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
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
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

				assertValidError( response, 400, 'invalid-language-code' );
				assert.include( response.body.message, invalidLanguage );
			} );
		} );

		it( 'invalid description', async () => {
			const invalidDescription = 'tab characters \t not allowed';
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', invalidDescription )
				.assertValidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-description' );
			assert.include( response.body.message, invalidDescription );
		} );

		it( 'description empty', async () => {
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', '' )
				.assertValidRequest().makeRequest();

			assertValidError( response, 400, 'description-empty' );
		} );

		it( 'description too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const limit = 250;
			const description = 'a'.repeat( limit + 1 );
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', description )
				.assertValidRequest().makeRequest();

			assertValidError( response, 400, 'description-too-long', { value: description, 'character-limit': limit } );
			assert.include( response.body.message, limit );
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
			const matchingItemId = createEntityResponse.entity.id;

			const response = await newSetItemDescriptionRequestBuilder( testItemId, language, description )
				.assertValidRequest().makeRequest();

			const context = { language, label: testEnLabel, description, 'matching-item-id': matchingItemId };
			assertValidError( response, 400, 'item-label-description-duplicate', context );
			assert.include( response.body.message, matchingItemId );
			assert.include( response.body.message, testEnLabel );
			assert.include( response.body.message, language );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', 'item description' )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', 'item description' )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'comment-too-long' );
			assert.include( response.body.message, '500' );
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

			assertValidError( response, 409, 'redirected-item' );
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

			assertValidError( response, 409, 'redirected-item' );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
		} );
	} );
} );
