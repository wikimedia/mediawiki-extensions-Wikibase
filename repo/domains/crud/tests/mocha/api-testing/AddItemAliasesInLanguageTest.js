'use strict';

const { assert, action } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newAddItemAliasesInLanguageRequestBuilder: newRequest,
	newCreateItemRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );

describe( newRequest().getRouteDescription(), () => {
	let testItemId;
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
		const createEntityResponse = await newCreateItemRequestBuilder(
			{ aliases: { en: [ existingEnglishAlias ], fr: [ existingFrenchAlias ] } }
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
		it( 'can create a new list of aliases with edit metadata omitted', async () => {
			const newAliases = [ 'first new alias', 'second new alias' ];
			const response = await newRequest( testItemId, 'de', newAliases )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, newAliases );
		} );

		it( 'can add to an existing list of aliases with edit metadata omitted', async () => {
			const response = await newRequest( testItemId, 'en', [ 'next english alias', existingEnglishAlias ] )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, [ existingEnglishAlias, 'next english alias' ] );
		} );

		it( 'can add aliases with edit metadata provided', async () => {
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
			const editSummary = 'omg look i made an edit';

			const language = 'fr';
			const newAlias = 'fr-alias';

			const response = await newRequest( testItemId, language, [ newAlias ] )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', editSummary )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, [ existingFrenchAlias, newAlias ] );
			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.deepEqual( editMetadata.tags, [ tag ] );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				`/* wbsetaliases-add:1|${language} */ ${newAlias}, ${editSummary}`
			);
			assert.strictEqual( editMetadata.user, user.username );
		} );

		it( 'can add a "mul" alias', async () => {
			const response = await newRequest( testItemId, 'mul', [ 'mul alias' ] )
				.withConfigOverride( 'wgWBRepoSettings', { enableMulLanguageCode: true } )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, [ 'mul alias' ] );
		} );
	} );

	describe( '400 error response', () => {
		it( 'invalid item id', async () => {
			const itemId = 'X123';
			const response = await newRequest( itemId, 'en', [ 'new alias' ] )
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
			const response = await newRequest( testItemId, '1e', [ 'new alias' ] )
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
			const response = await newRequest( testItemId, '1e', [ 'new alias' ] )
				.withEmptyJsonBody()
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'missing-field' );
			assert.deepEqual( response.body.context, { path: '', field: 'aliases' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( 'alias is empty', async () => {
			const response = await newRequest( testItemId, 'en', [ '' ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/aliases/0' } );
			assert.strictEqual( response.body.message, "Invalid value at '/aliases/0'" );
		} );

		it( 'alias list is empty', async () => {
			const response = await newRequest( testItemId, 'en', [] )
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
			const response = await newRequest( testItemId, 'en', [ alias ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'value-too-long', { path: '/aliases/0', limit: maxLength } );
			assert.strictEqual( response.body.message, 'The input value is too long' );
		} );

		it( 'alias contains invalid characters', async () => {
			const response = await newRequest( testItemId, 'en', [ 'tab characters \t not allowed' ] )
				.assertValidRequest()
				.makeRequest();

			const path = '/aliases/0';
			assertValidError( response, 400, 'invalid-value', { path } );
			assert.include( response.body.message, path );
		} );
	} );

	it( 'responds 404 if the item does not exist', async () => {
		const itemId = 'Q9999999';
		const response = await newRequest( itemId, 'en', [ 'potato' ] ).assertValidRequest().makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'item' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	it( 'responds 409 if the item is a redirect', async () => {
		const redirectTarget = testItemId;
		const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

		const response = await newRequest( redirectSource, 'en', [ 'potato' ] ).assertValidRequest().makeRequest();

		assertValidError( response, 409, 'redirected-item', { redirect_target: redirectTarget } );
		assert.include( response.body.message, redirectSource );
		assert.include( response.body.message, redirectTarget );
	} );
} );
