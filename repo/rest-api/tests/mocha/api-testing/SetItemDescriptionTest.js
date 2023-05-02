'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newSetItemDescriptionRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { formatTermEditSummary } = require( '../helpers/formatEditSummaries' );

function assertValidErrorResponse( response, responseBodyErrorCode ) {
	assert.header( response, 'Content-Language', 'en' );
	assert.strictEqual( response.body.code, responseBodyErrorCode );
}

function assertValid400Response( response, responseBodyErrorCode ) {
	expect( response ).to.have.status( 400 );
	assertValidErrorResponse( response, responseBodyErrorCode );
}

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

		// wait 1s before modifying to verify the last-modified timestamps are different
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

			assertValid400Response( response, 'invalid-item-id' );
			assert.include( response.body.message, invalidItemId );
		} );

		it( 'invalid language code', async () => {
			const invalidLanguage = 'xyz';
			const response = await newSetItemDescriptionRequestBuilder(
				testItemId,
				invalidLanguage,
				'item description'
			).assertValidRequest().makeRequest();

			assertValid400Response( response, 'invalid-language-code' );
			assert.include( response.body.message, invalidLanguage );
		} );

		it( 'invalid description', async () => {
			const invalidDescription = 'tab characters \t not allowed';
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', invalidDescription )
				.assertValidRequest().makeRequest();

			assertValid400Response( response, 'invalid-description' );
			assert.include( response.body.message, invalidDescription );
		} );

		it( 'description empty', async () => {
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', '' )
				.assertValidRequest().makeRequest();

			assertValid400Response( response, 'description-empty' );
		} );

		it( 'description too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const limit = 250;
			const tooLongDescription = 'a'.repeat( limit + 1 );
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', tooLongDescription )
				.assertValidRequest().makeRequest();

			assertValid400Response( response, 'description-too-long' );
			assert.include( response.body.message, limit );
			assert.deepEqual(
				response.body.context,
				{ value: tooLongDescription, 'character-limit': limit }
			);
		} );

		it( 'description is the same as the label', async () => {
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', testEnLabel )
				.assertValidRequest().makeRequest();

			assertValid400Response( response, 'label-description-same-value' );
			assert.include( response.body.message, 'en' );
			assert.deepEqual( response.body.context, { language: 'en' } );
		} );

		it( 'item with same label and description already exists', async () => {
			const description = `some-description-${utils.uniq()}`;
			const createEntityResponse = await entityHelper.createEntity( 'item', {
				labels: [ { language: 'en', value: testEnLabel } ],
				descriptions: [ { language: 'en', value: description } ]
			} );
			const matchingItemId = createEntityResponse.entity.id;

			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', description )
				.assertValidRequest().makeRequest();

			assertValid400Response( response, 'item-label-description-duplicate' );
			assert.include( response.body.message, matchingItemId );
			assert.include( response.body.message, testEnLabel );
			assert.include( response.body.message, 'en' );
			assert.deepEqual(
				response.body.context,
				{
					language: 'en',
					label: testEnLabel,
					description: description,
					'matching-item-id': matchingItemId
				}
			);
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', 'item description' )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] )
				.assertValidRequest()
				.makeRequest();

			assertValid400Response( response, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newSetItemDescriptionRequestBuilder( testItemId, 'en', 'item description' )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValid400Response( response, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

	} );

	describe( '404 error response', () => {
		it( 'item not found', async () => {
			const itemId = 'Q999999';
			const response = await newSetItemDescriptionRequestBuilder(
				itemId,
				'en',
				'test description'
			).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 404 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );
	} );

	describe( '409 error response', () => {
		it( 'item is a redirect', async () => {
			const redirectTarget = testItemId;
			const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

			const response = await newSetItemDescriptionRequestBuilder(
				redirectSource,
				'en',
				'test description'
			).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 409 );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
			assert.strictEqual( response.body.code, 'redirected-item' );
		} );
	} );

	describe( '415 error response', () => {
		it( 'unsupported media type', async () => {
			const contentType = 'multipart/form-data';
			const response = await newSetItemDescriptionRequestBuilder(
				'Q123',
				'en',
				'test description'
			).withHeader( 'content-type', contentType ).makeRequest();

			expect( response ).to.have.status( 415 );
			assert.strictEqual( response.body.message, `Unsupported Content-Type: '${contentType}'` );
		} );
	} );

} );
