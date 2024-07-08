'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newSetPropertyDescriptionRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { formatTermEditSummary } = require( '../helpers/formatEditSummaries' );
const { assertValidError } = require( '../helpers/responseValidator' );

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
			const response = await newSetPropertyDescriptionRequestBuilder( testPropertyId, languageCode, description )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, description );
		} );

		it( 'can add a description with edit metadata provided', async () => {
			const description = `new US English description ${utils.uniq()}`;
			const languageCode = 'en-us';
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
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
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
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

	describe( '400 error response', () => {

		it( 'invalid property ID', async () => {
			const response = await newSetPropertyDescriptionRequestBuilder( 'X11', 'en', 'description' )
				.assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'property_id' }
			);
		} );

		it( 'invalid language code', async () => {
			const response = await newSetPropertyDescriptionRequestBuilder( testPropertyId, 'xyz', 'description' )
				.assertValidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'language_code' }
			);
		} );

		it( 'invalid description', async () => {
			const invalidDescription = 'tab characters \t not allowed';
			const response = await newSetPropertyDescriptionRequestBuilder( testPropertyId, 'en', invalidDescription )
				.assertValidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-description' );
			assert.include( response.body.message, invalidDescription );
		} );

		it( 'description empty', async () => {
			const response = await newSetPropertyDescriptionRequestBuilder( testPropertyId, 'en', '' )
				.assertValidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/description' } );
			assert.strictEqual( response.body.message, "Invalid value at '/description'" );
		} );

		it( 'description too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const limit = 250;
			const description = 'a'.repeat( limit + 1 );
			const response = await newSetPropertyDescriptionRequestBuilder( testPropertyId, 'en', description )
				.assertValidRequest().makeRequest();

			assertValidError( response, 400, 'description-too-long', { value: description, 'character-limit': limit } );
			assert.include( response.body.message, limit );
		} );

		it( 'description is the same as the label', async () => {
			const response = await newSetPropertyDescriptionRequestBuilder( testPropertyId, 'en', testEnLabel )
				.assertValidRequest().makeRequest();

			assertValidError( response, 400, 'label-description-same-value', { language: 'en' } );
			assert.include( response.body.message, 'en' );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newSetPropertyDescriptionRequestBuilder( testPropertyId, 'en', 'description' )
				.withJsonBodyParam( 'tags', 'not an array' )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/tags' } );
		} );

		it( 'invalid edit tag', async () => {
			const response = await newSetPropertyDescriptionRequestBuilder( testPropertyId, 'en', 'description' )
				.withJsonBodyParam( 'tags', [ 'invalid tag' ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/tags/0' } );
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newSetPropertyDescriptionRequestBuilder( testPropertyId, 'en', 'description' )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

	} );

	describe( '404 error response', () => {
		it( 'property not found', async () => {
			const propertyId = 'P99999';
			const response = await newSetPropertyDescriptionRequestBuilder( propertyId, 'en', 'test description' )
				.assertValidRequest().makeRequest();

			assertValidError( response, 404, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );
	} );
} );
