'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newSetItemLabelRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { formatTermEditSummary } = require( '../helpers/formatEditSummaries' );
const { makeEtag } = require( '../helpers/httpHelper' );

describe( 'PUT /entities/items/{item_id}/labels/{language_code}', () => {
	let testItemId;
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
		const createEntityResponse = await entityHelper.createEntity( 'item', {
			labels: {
				en: { language: 'en', value: `english label ${utils.uniq()}` },
				fr: { language: 'fr', value: `étiquette française ${utils.uniq()}` }
			}
		} );
		testItemId = createEntityResponse.entity.id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

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
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
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
	} );

	describe( '400 error response', () => {
		it( 'invalid item id', async () => {
			const itemId = 'X123';
			const response = await newSetItemLabelRequestBuilder( itemId, 'en', 'test label' )
				.assertInvalidRequest()
				.makeRequest();

			assert.strictEqual( response.status, 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'invalid-item-id' );
			assert.include( response.body.message, itemId );
		} );

		it( 'invalid language code', async () => {
			const invalidLanguageCode = '1e';
			const response = await newSetItemLabelRequestBuilder( testItemId, invalidLanguageCode, 'test label' )
				.assertInvalidRequest()
				.makeRequest();

			assert.strictEqual( response.status, 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'invalid-language-code' );
			assert.include( response.body.message, invalidLanguageCode );
		} );

		it( 'label empty', async () => {
			const comment = 'Empty label';
			const emptyLabel = '';
			const response = await newSetItemLabelRequestBuilder( testItemId, 'en', emptyLabel )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assert.strictEqual( response.status, 400 );
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
			const response = await newSetItemLabelRequestBuilder( testItemId, 'en', labelTooLong )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assert.strictEqual( response.status, 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'label-too-long' );
			assert.strictEqual( response.body.message, 'Label must be no more than ' + maxLabelLength +
				' characters long' );
			assert.deepEqual(
				response.body.context,
				{ value: labelTooLong, 'character-limit': maxLabelLength }
			);

		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newSetItemLabelRequestBuilder( testItemId, 'en', 'test label' )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assert.strictEqual( response.status, 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newSetItemLabelRequestBuilder( testItemId, 'en', 'test label' )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] )
				.assertValidRequest()
				.makeRequest();

			assert.strictEqual( response.status, 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid bot flag', async () => {
			const response = await newSetItemLabelRequestBuilder( testItemId, 'en', 'test label' )
				.withJsonBodyParam( 'bot', 'should be a boolean' )
				.assertInvalidRequest()
				.makeRequest();

			assert.strictEqual( response.status, 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );
	} );

	describe( '404 error response', () => {
		it( 'item not found', async () => {
			const itemId = 'Q999999';
			const response = await newSetItemLabelRequestBuilder( itemId, 'en', 'test label' )
				.assertValidRequest()
				.makeRequest();

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

			const response = await newSetItemLabelRequestBuilder( redirectSource, 'en', 'test label' )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 409 );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
			assert.strictEqual( response.body.code, 'redirected-item' );
		} );
	} );

	describe( '415 error response', () => {
		it( 'unsupported media type', async () => {
			const contentType = 'multipart/form-data';
			const response = await newSetItemLabelRequestBuilder(
				testItemId,
				'en',
				'test label'
			).withHeader( 'content-type', contentType ).makeRequest();

			expect( response ).to.have.status( 415 );
			assert.strictEqual( response.body.message, `Unsupported Content-Type: '${contentType}'` );
		} );
	} );

} );
