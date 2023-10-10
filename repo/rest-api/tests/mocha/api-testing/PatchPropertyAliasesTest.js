'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newPatchPropertyAliasesRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );

describe( newPatchPropertyAliasesRequestBuilder().getRouteDescription(), () => {
	let testPropertyId;
	let originalLastModified;
	let originalRevisionId;
	const languageWithExistingAlias = 'en';
	const existingEnAlias = `en-alias-${utils.uniq()}`;

	before( async function () {
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
	} );

} );
