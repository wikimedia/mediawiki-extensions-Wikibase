'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const { assert, utils } = require( 'api-testing' );
const { newPatchItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const entityHelper = require( '../helpers/entityHelper' );
const { makeEtag } = require( '../helpers/httpHelper' );

describe( newPatchItemRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let originalLastModified;
	let originalRevisionId;
	let predicatePropertyId;

	before( async function () {
		testItemId = ( await entityHelper.createEntity( 'item', {
			labels: [ { language: 'en', value: `some-label-${utils.uniq()}` } ],
			descriptions: [ { language: 'en', value: `some-description-${utils.uniq()}` } ],
			aliases: [ { language: 'fr', value: 'croissant' } ]
		} ) ).entity.id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		predicatePropertyId = ( await entityHelper.createEntity( 'property', { datatype: 'string' } ) ).entity.id;

		// wait 1s before next test to ensure the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '200 OK', () => {

		it( 'can patch an item', async () => {
			const newLabel = `neues deutsches label ${utils.uniq()}`;
			const updatedDescription = `changed description ${utils.uniq()}`;
			const newStatementValue = 'new statement';
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[
					{ op: 'add', path: '/labels/de', value: newLabel },
					{ op: 'replace', path: '/descriptions/en', value: updatedDescription },
					{ op: 'remove', path: '/aliases/fr' },
					{
						op: 'add',
						path: `/statements/${predicatePropertyId}`,
						value: [ {
							property: { id: predicatePropertyId },
							value: { type: 'value', content: newStatementValue }
						} ]
					}
				]
			).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.id, testItemId );
			assert.strictEqual( response.body.labels.de, newLabel );
			assert.strictEqual( response.body.descriptions.en, updatedDescription );
			assert.isEmpty( response.body.aliases );
			assert.strictEqual( response.body.statements[ predicatePropertyId ][ 0 ].value.content, newStatementValue );
			assert.match(
				response.body.statements[ predicatePropertyId ][ 0 ].id,
				new RegExp( `^${testItemId}\\$[A-Z0-9]{8}(-[A-Z0-9]{4}){3}-[A-Z0-9]{12}$`, 'i' )
			);
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
			assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
			assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
		} );

	} );

} );
