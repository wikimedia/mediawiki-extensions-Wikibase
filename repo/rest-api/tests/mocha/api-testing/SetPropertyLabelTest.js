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
} );
