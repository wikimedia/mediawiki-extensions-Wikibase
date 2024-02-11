'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newPatchSitelinksRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { createLocalSitelink, getLocalSiteId } = require( '../helpers/entityHelper' );
const { makeEtag } = require( '../helpers/httpHelper' );

describe( newPatchSitelinksRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let siteId;
	const linkedArticle = utils.title( 'Article-linked-to-test-item' );
	let originalLastModified;
	let originalRevisionId;

	function assertValidResponse( response, status, title, badges ) {
		expect( response ).to.have.status( status );
		assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
		assert.strictEqual( response.body[ siteId ].title, title );
		assert.deepEqual( response.body[ siteId ].badges, badges );
		assert.include( response.body[ siteId ].url, title );
	}

	before( async function () {
		testItemId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
		await createLocalSitelink( testItemId, linkedArticle );
		siteId = await getLocalSiteId();

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		// wait 1s before next test to ensure the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '200 OK', () => {
		it( 'can add a sitelink', async () => {
			const sitelink = { title: utils.title( 'test-title-' ), badges: [ 'Q123' ] };
			const response = await newPatchSitelinksRequestBuilder(
				testItemId,
				[ { op: 'add', path: `/${siteId}`, value: sitelink } ]
			).makeRequest();

			assertValidResponse( response, 200, sitelink.title, sitelink.badges );
		} );

		it( 'can patch sitelinks with edit metadata', async () => {
			const sitelink = { title: utils.title( 'test-title-' ), badges: [ 'Q123' ] };
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const editSummary = 'I made a patch';

			const response = await newPatchSitelinksRequestBuilder(
				testItemId,
				[ { op: 'add', path: `/${siteId}`, value: sitelink } ]
			).withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', editSummary )
				.withUser( user )
				.assertValidRequest().makeRequest();

			assertValidResponse( response, 200, sitelink.title, sitelink.badges );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
		} );
	} );

} );
