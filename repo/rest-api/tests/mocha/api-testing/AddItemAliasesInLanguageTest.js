'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newAddItemAliasesInLanguageRequestBuilder: newRequest } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );

describe( newRequest().getRouteDescription(), () => {
	let testItemId;
	let originalLastModified;
	let originalRevisionId;
	const existingEnglishAlias = 'first english alias';

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
			aliases: { en: [
				{ language: 'en', value: existingEnglishAlias }
			] }
		} );
		testItemId = createEntityResponse.entity.id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		// wait 1s before modifying aliases to verify the last-modified timestamps are different
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
	} );

	describe( '400 error response', () => {
		it( 'invalid item id', async () => {
			const itemId = 'X123';
			const response = await newRequest( itemId, 'en', [ 'new alias' ] )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'invalid-item-id' );
			assert.include( response.body.message, itemId );
		} );

		it( 'invalid language code', async () => {
			const invalidLanguageCode = '1e';
			const response = await newRequest( testItemId, invalidLanguageCode, [ 'new alias' ] )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'invalid-language-code' );
			assert.include( response.body.message, invalidLanguageCode );
		} );

		it( 'alias is empty', async () => {
			const response = await newRequest( testItemId, 'en', [ '' ] )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'alias-empty' );
			assert.strictEqual( response.body.message, 'Alias must not be empty' );
		} );

		it( 'alias list is empty', async () => {
			const response = await newRequest( testItemId, 'en', [] )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'alias-list-empty' );
			assert.strictEqual( response.body.message, 'Alias list must not be empty' );
		} );

		it( 'alias too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLabelLength = 250;
			const aliasTooLong = 'x'.repeat( maxLabelLength + 1 );
			const response = await newRequest( testItemId, 'en', [ aliasTooLong ] )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'alias-too-long' );
			assert.strictEqual(
				response.body.message,
				`Alias must be no more than ${maxLabelLength} characters long`
			);
			assert.deepEqual(
				response.body.context,
				{ value: aliasTooLong, 'character-limit': maxLabelLength }
			);

		} );

		it( 'alias contains invalid characters', async () => {
			const invalidAlias = 'tab characters \t not allowed';
			const response = await newRequest( testItemId, 'en', [ invalidAlias ] )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'invalid-alias' );
			assert.include( response.body.message, invalidAlias );
		} );

		it( 'duplicate input aliases', async () => {
			const duplicateAlias = 'foo';
			const response = await newRequest(
				testItemId,
				'en',
				[ duplicateAlias, 'foo', duplicateAlias ]
			).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'duplicate-alias' );
			assert.include( response.body.message, duplicateAlias );
		} );

		it( 'input alias already exist', async () => {
			const response = await newRequest(
				testItemId,
				'en',
				[ existingEnglishAlias ]
			).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'duplicate-alias' );
			assert.include( response.body.message, existingEnglishAlias );
		} );
	} );
} );
