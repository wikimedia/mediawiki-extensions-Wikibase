'use strict';

const { assert, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newAddPropertyAliasesInLanguageRequestBuilder: newRequest } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );

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

		// wait 1s before modifying aliases to verify the last-modified timestamps are different
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
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const editSummary = 'omg look i made an edit';

			const language = 'fr';
			const newAlias = 'fr-alias';

			const response = await newRequest( testPropertyId, language, [ newAlias ] )
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
		it( 'invalid property id', async () => {
			const propertyId = 'X123';
			const response = await newRequest( propertyId, 'en', [ 'new alias' ] )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'invalid-property-id' );
			assert.include( response.body.message, propertyId );
		} );

		it( 'invalid language code', async () => {
			const invalidLanguageCode = '1e';
			const response = await newRequest( testPropertyId, invalidLanguageCode, [ 'new alias' ] )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'invalid-language-code' );
			assert.include( response.body.message, invalidLanguageCode );
		} );

		it( 'alias is empty', async () => {
			const response = await newRequest( testPropertyId, 'en', [ '' ] )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'alias-empty' );
			assert.strictEqual( response.body.message, 'Alias must not be empty' );
		} );

		it( 'alias list is empty', async () => {
			const response = await newRequest( testPropertyId, 'en', [] )
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
			const response = await newRequest( testPropertyId, 'en', [ aliasTooLong ] )
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
			const response = await newRequest( testPropertyId, 'en', [ invalidAlias ] )
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
				testPropertyId,
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
				testPropertyId,
				'en',
				[ existingEnglishAlias ]
			).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'duplicate-alias' );
			assert.include( response.body.message, existingEnglishAlias );
		} );
	} );

	it( 'responds 404 if the property does not exist', async () => {
		const propertyId = 'P9999999';
		const response = await newRequest( propertyId, 'en', [ 'my property alias' ] )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 404 );
		assert.strictEqual( response.header[ 'content-language' ], 'en' );
		assert.strictEqual( response.body.code, 'property-not-found' );
		assert.include( response.body.message, propertyId );
	} );

	describe( '415 error response', () => {
		it( 'unsupported media type', async () => {
			const contentType = 'multipart/form-data';
			const response = await newRequest( testPropertyId, 'en', [ 'my property alias' ] )
				.withHeader( 'content-type', contentType ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 415 );
			assert.strictEqual( response.body.message, `Unsupported Content-Type: '${contentType}'` );
		} );
	} );

} );
