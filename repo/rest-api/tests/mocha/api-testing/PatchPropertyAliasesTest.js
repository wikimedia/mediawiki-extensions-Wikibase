'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newPatchPropertyAliasesRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const { formatLabelsEditSummary } = require( '../helpers/formatEditSummaries' );

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

describe( newPatchPropertyAliasesRequestBuilder().getRouteDescription(), () => {
	let testPropertyId;
	let originalLastModified;
	let originalRevisionId;
	const languageWithExistingAlias = 'en';
	const existingEnAlias = `en-alias-${utils.uniq()}`;

	before( async () => {
		const aliases = {};
		aliases[ languageWithExistingAlias ] = [ { language: languageWithExistingAlias, value: existingEnAlias } ];
		testPropertyId = ( await entityHelper.createEntity( 'property', {
			datatype: 'string',
			aliases
		} ) ).entity.id;

		const testPropertyCreationMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
		originalLastModified = new Date( testPropertyCreationMetadata.timestamp );
		originalRevisionId = testPropertyCreationMetadata.revid;

		// wait 1s before modifying to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '200 OK', () => {
		it( 'can patch aliases', async () => {
			const newDeAlias = `de-alias-${utils.uniq()}`;
			const newEnAlias = `en-alias-${utils.uniq()}`;
			const newEnAliasWithTrailingWhitespace = `\t  ${newEnAlias}  `;
			const response = await newPatchPropertyAliasesRequestBuilder(
				testPropertyId,
				[
					{ op: 'add', path: '/de', value: [ newDeAlias ] },
					{ op: 'add', path: '/en/-', value: newEnAliasWithTrailingWhitespace }
				]
			).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.deepEqual( response.body.de, [ newDeAlias ] );
			assert.deepEqual( response.body.en, [ existingEnAlias, newEnAlias ] );
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
			assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
			assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
		} );

		it( 'can patch aliases providing edit metadata', async () => {
			const newDeAlias = `de-alias-${utils.uniq()}`;
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const editSummary = 'I made a patch';
			const response = await newPatchPropertyAliasesRequestBuilder(
				testPropertyId,
				[ { op: 'add', path: '/de', value: [ newDeAlias ] } ]
			).withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', editSummary )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatLabelsEditSummary( 'update-languages-short', 'de', editSummary )
			);
		} );
	} );

	describe( '400 Bad Request', () => {
		it( 'property ID is invalid', async () => {
			const propertyId = 'X123';
			const response = await newPatchPropertyAliasesRequestBuilder( propertyId, [] )
				.assertInvalidRequest().makeRequest();

			assertValid400Response( response, 'invalid-property-id', { 'property-id': propertyId } );
			assert.include( response.body.message, propertyId );
		} );

		testValidatesPatch( ( patch ) => newPatchPropertyAliasesRequestBuilder( testPropertyId, patch ) );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

			assertValid400Response( response, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

			assertValid400Response( response, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'comment' );
			assert.strictEqual( response.body.expectedType, 'string' );
		} );
	} );

	describe( '422 Unprocessable Content', () => {
		it( 'empty alias', async () => {
			const language = 'de';
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/${language}`, value: [ '' ] }
			] ).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 422 );
			assert.strictEqual( response.body.code, 'patched-alias-empty' );
			assert.include( response.body.message, language );
			assert.deepEqual( response.body.context, { language } );
		} );

		it( 'alias too long', async () => {
			const language = 'de';
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const tooLongAlias = 'x'.repeat( maxLength + 1 );
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/${language}`, value: [ tooLongAlias ] }
			] ).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 422 );
			assert.strictEqual( response.body.code, 'patched-alias-too-long' );
			assert.include( response.body.message, language );
			assert.deepEqual(
				response.body.context,
				{ language, value: tooLongAlias, 'character-limit': maxLength }
			);
		} );

		it( 'duplicate alias', async () => {
			const language = 'en';
			const duplicate = 'tomato';
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/${language}`, value: [ duplicate, duplicate ] }
			] ).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 422 );
			assert.strictEqual( response.body.code, 'patched-duplicate-alias' );
			assert.include( response.body.message, language );
			assert.include( response.body.message, duplicate );
			assert.deepEqual( response.body.context, { language, value: duplicate } );
		} );

		it( 'alias contains invalid characters', async () => {
			const language = 'en';
			const invalidAlias = 'tab\t tab\t tab';
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/${language}`, value: [ invalidAlias ] }
			] ).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 422 );
			assert.strictEqual( response.body.code, 'patched-alias-invalid' );
			assert.include( response.body.message, language );
			assert.include( response.body.message, invalidAlias );
			assert.deepEqual( response.body.context, { language, value: invalidAlias } );
		} );

		it( 'invalid language code', async () => {
			const language = 'not-a-valid-language';
			const response = await newPatchPropertyAliasesRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/${language}`, value: [ 'alias' ] }
			] ).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 422 );
			assert.strictEqual( response.body.code, 'patched-alias-invalid-language-code' );
			assert.include( response.body.message, language );
			assert.deepEqual( response.body.context, { language } );
		} );
	} );

	it( '404 if the property does not exist', async () => {
		const propertyId = 'P999999999';
		const response = await newPatchPropertyAliasesRequestBuilder( propertyId, [] )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 404 );
		assert.strictEqual( response.body.code, 'property-not-found' );
		assert.include( response.body.message, propertyId );
	} );

} );
