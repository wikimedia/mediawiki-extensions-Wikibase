'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newSetSitelinkRequestBuilder,
	newRemoveSitelinkRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { createEntity, createLocalSitelink, getLocalSiteId } = require( '../helpers/entityHelper' );

describe( newSetSitelinkRequestBuilder().getRouteDescription(), () => {
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
		assert.strictEqual( response.body.title, title );
		assert.deepEqual( response.body.badges, badges );
		assert.include( response.body.url, title );
	}

	before( async () => {
		const createItemResponse = await createEntity( 'item', {} );
		testItemId = createItemResponse.entity.id;

		await createLocalSitelink( testItemId, linkedArticle );
		siteId = await getLocalSiteId();

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		// wait 1s before next test to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '20x success response ', () => {
		it( 'can add a sitelink of an item with edit metadata omitted', async () => {
			await newRemoveSitelinkRequestBuilder( testItemId, siteId ).assertValidRequest().makeRequest();

			const newSitelink = { title: utils.title( 'test-title-' ), badges: [ 'Q123' ] };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, newSitelink )
				.assertValidRequest()
				.makeRequest();

			assertValidResponse( response, 201, newSitelink.title, newSitelink.badges );
		} );

		it( 'can replace the sitelink of an item with edit metadata omitted', async () => {
			const newSitelink = { title: utils.title( 'test-title-' ), badges: [ 'Q123' ] };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, newSitelink )
				.assertValidRequest()
				.makeRequest();

			assertValidResponse( response, 200, newSitelink.title, newSitelink.badges );
		} );

		it( 'idempotency check: can set the same sitelink twice', async () => {
			const newSitelink = { title: utils.title( 'test-title-' ), badges: [ 'Q123' ] };
			let response = await newSetSitelinkRequestBuilder( testItemId, siteId, newSitelink )
				.assertValidRequest()
				.makeRequest();

			assertValidResponse( response, 200, newSitelink.title, newSitelink.badges );

			response = await newSetSitelinkRequestBuilder( testItemId, siteId, newSitelink )
				.assertValidRequest()
				.makeRequest();

			assertValidResponse( response, 200, newSitelink.title, newSitelink.badges );
		} );
	} );
} );
