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
	let testSitelink;
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
		testSitelink = { title: utils.title( 'test-title-' ), badges: [ 'Q123' ] };

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

	describe( '400', () => {
		it( 'invalid item ID', async () => {
			const invalidItemId = 'X123';
			const response = await newSetSitelinkRequestBuilder( invalidItemId, siteId, testSitelink )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.strictEqual( response.body.code, 'invalid-item-id' );
			assert.include( response.body.message, invalidItemId );
		} );

		it( 'invalid site ID', async () => {
			const invalidSiteId = 'not-a-valid-site-id';
			const response = await newSetSitelinkRequestBuilder( testItemId, invalidSiteId, testSitelink )
				// .assertInvalidRequest() - valid per OAS because it only checks whether it is a string
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.header( response, 'Content-Language', 'en' );
			assert.strictEqual( response.body.code, 'invalid-site-id' );
			assert.include( response.body.message, invalidSiteId );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, testSitelink )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, testSitelink )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, testSitelink )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, testSitelink )
				.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, testSitelink )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'comment' );
			assert.strictEqual( response.body.expectedType, 'string' );
		} );
	} );

	describe( '404', () => {
		it( 'item not found', async () => {
			const itemId = 'Q999999';
			const response = await newSetSitelinkRequestBuilder( itemId, siteId, testSitelink )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 404 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );
	} );

	describe( '409', () => {
		it( 'item is a redirect', async () => {
			const redirectTarget = testItemId;
			const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

			const response = await newSetSitelinkRequestBuilder( redirectSource, siteId, testSitelink )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 409 );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
			assert.strictEqual( response.body.code, 'redirected-item' );
		} );
	} );
} );
