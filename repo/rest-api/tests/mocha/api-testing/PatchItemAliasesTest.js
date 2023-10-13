'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newPatchItemAliasesRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { formatLabelsEditSummary } = require( '../helpers/formatEditSummaries' );

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

} );
