'use strict';

const { assert, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newAddPropertyAliasesInLanguageRequestBuilder: newRequest } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );

describe( newRequest().getRouteDescription(), () => {
	let testPropertyId;
	let originalLastModified;
	let originalRevisionId;
	const existingEnglishAlias = 'first english alias';
	const existingFrenchAlias = 'first french alias';

	function assertValidResponse( response, aliases ) {
		assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
		assert.deepEqual( response.body, aliases );
	}

	function assertValid200Response( response, aliases ) {
		expect( response ).to.have.status( 200 );
		assertValidResponse( response, aliases );
	}

	function assertValid201Response( response, aliases ) {
		expect( response ).to.have.status( 201 );
		assertValidResponse( response, aliases );
	}

	before( async () => {
		const createEntityResponse = await entityHelper.createEntity( 'property', {
			datatype: 'string',
			aliases: {
				en: [ { language: 'en', value: existingEnglishAlias } ],
				fr: [ { language: 'fr', value: existingFrenchAlias } ]
			}
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
		it( 'can add to an existing list of aliases with edit metadata omitted', async () => {
			const response = await newRequest( testPropertyId, 'en', [ 'next english alias' ] )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response(
				response,
				[ existingEnglishAlias, 'next english alias' ]
			);
		} );

		it( 'can add aliases with edit metadata provided', async () => {
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
			const editSummary = 'omg look i made an edit';

			const language = 'fr';
			const newAlias = 'fr-alias';

			const response = await newRequest( testPropertyId, language, [ newAlias, existingFrenchAlias ] )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', editSummary )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, [ existingFrenchAlias, newAlias ] );
			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
			assert.deepEqual( editMetadata.tags, [ tag ] );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				`/* wbsetaliases-add:1|${language} */ ${newAlias}, ${editSummary}`
			);
			assert.strictEqual( editMetadata.user, user.username );
		} );

		it( 'can create a new list of aliases with edit metadata omitted', async () => {
			const newAliases = [ 'first de alias', 'second de alias' ];
			const response = await newRequest( testPropertyId, 'de', newAliases )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, newAliases );
		} );
	} );

	describe( '400 error response', () => {
		it( 'invalid request data', async () => {
			const response = await newRequest( testPropertyId, 'en', 'invalid alias type' )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.strictEqual( response.body.message, "Invalid value at '/aliases'" );
		} );

		it( 'invalid property id', async () => {
			const response = await newRequest( 'X123', 'en', [ 'new alias' ] )
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
			const response = await newRequest( testPropertyId, '1e', [ 'new alias' ] )
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
			const response = await newRequest( testPropertyId, '1e', [ 'new alias' ] )
				.withEmptyJsonBody()
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'missing-field' );
			assert.deepEqual( response.body.context, { path: '', field: 'aliases' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( 'alias is empty', async () => {
			const response = await newRequest( testPropertyId, 'en', [ '' ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/aliases/0' } );
			assert.strictEqual( response.body.message, "Invalid value at '/aliases/0'" );
		} );

		it( 'alias list is empty', async () => {
			const response = await newRequest( testPropertyId, 'en', [] )
				.assertValidRequest()
				.makeRequest();

			const path = '/aliases';
			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.include( response.body.message, path );
		} );

		it( 'alias too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const alias = 'x'.repeat( maxLength + 1 );
			const response = await newRequest( testPropertyId, 'en', [ alias ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'value-too-long', { path: '/aliases/0', limit: maxLength } );
			assert.strictEqual( response.body.message, 'The input value is too long' );

		} );

		it( 'alias contains invalid characters', async () => {
			const response = await newRequest( testPropertyId, 'en', [ 'tab characters \t not allowed' ] )
				.assertValidRequest()
				.makeRequest();

			const path = '/aliases/0';
			assertValidError( response, 400, 'invalid-value', { path } );
			assert.include( response.body.message, path );
		} );
	} );

	it( 'responds 404 if the property does not exist', async () => {
		const propertyId = 'P9999999';
		const response = await newRequest( propertyId, 'en', [ 'my property alias' ] )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'property' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );
} );
