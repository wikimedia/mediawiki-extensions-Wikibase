'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newPatchItemDescriptionsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newPatchItemDescriptionsRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let testEnDescription;
	const languageWithExistingLabel = 'en';

	before( async function () {
		testEnDescription = `English Description ${utils.uniq()}`;
		testItemId = ( await entityHelper.createEntity( 'item', {
			descriptions: [ { language: languageWithExistingLabel, value: testEnDescription } ]
		} ) ).entity.id;

		// wait 1s before modifying labels to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '200 OK', () => {
		it( 'can add a description', async () => {
			const description = `Neues Deutsches Beschreibung ${utils.uniq()}`;
			const response = await newPatchItemDescriptionsRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/de', value: description } ]
			).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.de, description );
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		} );

		it( 'trims whitespace around the description', async () => {
			const description = `spacey ${utils.uniq()}`;
			const response = await newPatchItemDescriptionsRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/de', value: ` \t${description}  ` } ]
			).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.de, description );
		} );

		it( 'can patch descriptions with edit metadata', async () => {
			const description = `${utils.uniq()} وصف عربي جديد`;
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const comment = 'I made a patch';
			const response = await newPatchItemDescriptionsRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/ar', value: description } ]
			)
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.ar, description );
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.include( editMetadata.tags, tag );
			assert.include( editMetadata.comment, comment );
			assert.property( editMetadata, 'bot' );
		} );
	} );

} );
