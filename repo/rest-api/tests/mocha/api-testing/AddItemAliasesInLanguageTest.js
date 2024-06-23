'use strict';

const { assert, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newAddItemAliasesInLanguageRequestBuilder: newRequest } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );

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
		const createEntityResponse = await entityHelper.createEntity( 'item', {
			aliases: {
				en: [ { language: 'en', value: existingEnglishAlias } ],
				fr: [ { language: 'fr', value: existingFrenchAlias } ]
			}
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

	describe( '20x success response ', () => {
		it( 'can create a new list of aliases with edit metadata omitted', async () => {
			const newAliases = [ 'first new alias', 'second new alias' ];
			const response = await newRequest( testItemId, 'de', newAliases )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, newAliases );
		} );

		it( 'can add to an existing list of aliases with edit metadata omitted', async () => {
			const response = await newRequest( testItemId, 'en', [ 'next english alias' ] )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response(
				response,
				[ existingEnglishAlias, 'next english alias' ]
			);
		} );

		it( 'can add aliases with edit metadata provided', async () => {
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
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
				.withHeader( 'X-Wikibase-Ci-Enable-Mul', 'true' )
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

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newRequest( testItemId, 'en', [ 'new alias' ] )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newRequest( testItemId, 'en', [ 'new alias' ] )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid bot flag', async () => {
			const response = await newRequest( testItemId, 'en', [ 'new alias' ] )
				.withJsonBodyParam( 'bot', 'should be a boolean' )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/bot' } );
		} );

		it( 'invalid comment', async () => {
			const response = await newRequest( testItemId, 'en', [ 'new alias' ] )
				.withJsonBodyParam( 'comment', 123 )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/comment' } );
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

		it( 'alias is empty', async () => {
			const response = await newRequest( testItemId, 'en', [ '' ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'alias-empty' );
			assert.strictEqual( response.body.message, 'Alias must not be empty' );
		} );

		it( 'alias list is empty', async () => {
			const response = await newRequest( testItemId, 'en', [] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'alias-list-empty' );
			assert.strictEqual( response.body.message, 'Alias list must not be empty' );
		} );

		it( 'alias too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const alias = 'x'.repeat( maxLength + 1 );
			const response = await newRequest( testItemId, 'en', [ alias ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'alias-too-long', { value: alias, 'character-limit': maxLength } );
			assert.strictEqual( response.body.message, `Alias must be no more than ${maxLength} characters long` );
		} );

		it( 'alias contains invalid characters', async () => {
			const invalidAlias = 'tab characters \t not allowed';
			const response = await newRequest( testItemId, 'en', [ invalidAlias ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-alias', { alias: invalidAlias } );
			assert.include( response.body.message, invalidAlias );
		} );

		it( 'duplicate input aliases', async () => {
			const duplicateAlias = 'foo';
			const response = await newRequest( testItemId, 'en', [ duplicateAlias, 'foo', duplicateAlias ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'duplicate-alias', { alias: duplicateAlias } );
			assert.include( response.body.message, duplicateAlias );
		} );

		it( 'input alias already exist', async () => {
			const response = await newRequest( testItemId, 'en', [ existingEnglishAlias ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'duplicate-alias', { alias: existingEnglishAlias } );
			assert.include( response.body.message, existingEnglishAlias );
		} );
	} );

	it( 'responds 404 if the item does not exist', async () => {
		const itemId = 'Q9999999';
		const response = await newRequest( itemId, 'en', [ 'potato' ] ).assertValidRequest().makeRequest();

		assertValidError( response, 404, 'item-not-found' );
		assert.include( response.body.message, itemId );
	} );

	it( 'responds 409 if the item is a redirect', async () => {
		const redirectTarget = testItemId;
		const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

		const response = await newRequest( redirectSource, 'en', [ 'potato' ] ).assertValidRequest().makeRequest();

		assertValidError( response, 409, 'redirected-item' );
		assert.include( response.body.message, redirectSource );
		assert.include( response.body.message, redirectTarget );
	} );
} );
