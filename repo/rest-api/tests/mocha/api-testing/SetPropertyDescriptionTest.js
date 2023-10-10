'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newSetPropertyDescriptionRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { formatTermEditSummary } = require( '../helpers/formatEditSummaries' );

describe( newSetPropertyDescriptionRequestBuilder().getRouteDescription(), () => {
	let testPropertyId;
	let testEnLabel;
	let originalLastModified;
	let originalRevisionId;

	before( async () => {
		testEnLabel = `some-label-${utils.uniq()}`;
		const createEntityResponse = await entityHelper.createEntity( 'property', {
			labels: [ { language: 'en', value: testEnLabel } ],
			descriptions: [ { language: 'en', value: `some-description-${utils.uniq()}` } ],
			datatype: 'string'
		} );
		testPropertyId = createEntityResponse.entity.id;

		const testPropertyCreationMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
		originalLastModified = new Date( testPropertyCreationMetadata.timestamp );
		originalRevisionId = testPropertyCreationMetadata.revid;

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
			const response = await newSetPropertyDescriptionRequestBuilder( testPropertyId, languageCode, description )
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

			const response = await newSetPropertyDescriptionRequestBuilder( testPropertyId, languageCode, description )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, description );

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
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
			const response = await newSetPropertyDescriptionRequestBuilder( testPropertyId, languageCode, description )
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

			const response = await newSetPropertyDescriptionRequestBuilder( testPropertyId, languageCode, description )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, description );

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
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
			let response = await newSetPropertyDescriptionRequestBuilder( testPropertyId, languageCode, newDescription )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, newDescription );

			response = await newSetPropertyDescriptionRequestBuilder( testPropertyId, languageCode, newDescription )
				.withJsonBodyParam( 'comment', 'omg look, i can set the same description again' )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, newDescription );
		} );

	} );

} );
