'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newPatchItemAliasesRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { formatLabelsEditSummary } = require( '../helpers/formatEditSummaries' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );

function assertValid400Response( response, responseBodyErrorCode, context = null ) {
	expect( response ).to.have.status( 400 );
	assert.header( response, 'Content-Language', 'en' );
	assert.strictEqual( response.body.code, responseBodyErrorCode );
	if ( context === null ) {
		assert.notProperty( response.body, 'context' );
	} else {
		assert.deepStrictEqual( response.body.context, context );
	}
}

describe( newPatchItemAliasesRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let testAlias;
	let originalLastModified;
	let originalRevisionId;
	const testLanguage = 'en';

	before( async function () {
		testAlias = 'English Alias';

		testItemId = ( await entityHelper.createEntity( 'item', {
			labels: [ { language: testLanguage, value: `English Label ${utils.uniq()}` } ],
			descriptions: [ { language: testLanguage, value: `English Description ${utils.uniq()}` } ],
			aliases: { en: [ { language: testLanguage, value: testAlias } ] }
		} ) ).entity.id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		// wait 1s before modifying to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '200 OK', () => {
		it( 'can add another alias', async () => {
			const alias = 'another English alias';
			const response = await newPatchItemAliasesRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/en/-', value: alias } ]
			).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.include( response.body.en, testAlias );
			assert.include( response.body.en, alias );
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
			assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
			assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
		} );

		it( 'trims whitespace around the alias', async () => {
			const alias = 'spacey alias';
			const response = await newPatchItemAliasesRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/en/-', value: ` \t${alias}  ` } ]
			).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.include( response.body.en, testAlias );
			assert.include( response.body.en, alias );
		} );

		it( 'can patch aliases providing edit metadata', async () => {
			const newDeAlias = `de-alias-${utils.uniq()}`;
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const editSummary = 'I made a patch';
			const response = await newPatchItemAliasesRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/de', value: [ newDeAlias ] } ]
			).withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', editSummary )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.include( response.body.en, testAlias );
			assert.include( response.body.de, newDeAlias );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatLabelsEditSummary( 'update-languages-short', 'de', editSummary )
			);
		} );
	} );

	describe( '400 Bad Request', () => {
		it( 'item ID is invalid', async () => {
			const itemId = 'X123';
			const response = await newPatchItemAliasesRequestBuilder( itemId, [] )
				.assertInvalidRequest().makeRequest();

			assertValid400Response( response, 'invalid-item-id' );
			assert.include( response.body.message, itemId );
		} );

		testValidatesPatch( ( patch ) => newPatchItemAliasesRequestBuilder( testItemId, patch ) );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newPatchItemAliasesRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

			assertValid400Response( response, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newPatchItemAliasesRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

			assertValid400Response( response, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newPatchItemAliasesRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newPatchItemAliasesRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newPatchItemAliasesRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'comment' );
			assert.strictEqual( response.body.expectedType, 'string' );
		} );
	} );

} );
