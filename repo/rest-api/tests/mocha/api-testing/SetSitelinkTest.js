'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newSetSitelinkRequestBuilder,
	newRemoveSitelinkRequestBuilder,
	ALLOWED_BADGES
} = require( '../helpers/RequestBuilderFactory' );
const { formatSitelinkEditSummary } = require( '../helpers/formatEditSummaries' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { createEntity, createLocalSitelink, getLocalSiteId } = require( '../helpers/entityHelper' );

describe( newSetSitelinkRequestBuilder().getRouteDescription(), () => {
	let testItemId;
	let siteId;
	let testSitelink;
	const linkedArticle = utils.title( 'Article-linked-to-test-item-' );
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

	function assertValidErrorResponse( response, responseBodyErrorCode ) {
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, responseBodyErrorCode );
	}

	before( async () => {
		const createItemResponse = await createEntity( 'item', {} );
		testItemId = createItemResponse.entity.id;
		testSitelink = { title: utils.title( 'test-title-' ), badges: [ ALLOWED_BADGES[ 2 ] ] };

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

		it( 'can add a sitelink with badges and edit metadata', async () => {
			await newRemoveSitelinkRequestBuilder( testItemId, siteId ).assertValidRequest().makeRequest();

			const testTitle = utils.title( 'test-title-' );
			const testBadges = [ ALLOWED_BADGES[ 0 ], ALLOWED_BADGES[ 1 ] ];
			const testComment = 'omg – i created a sitelink!';

			const newSitelink = { title: testTitle, badges: testBadges };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, newSitelink )
				.assertValidRequest()
				.withJsonBodyParam( 'comment', testComment )
				.makeRequest();

			assertValidResponse( response, 201, newSitelink.title, newSitelink.badges );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.strictEqual(
				editMetadata.comment,
				formatSitelinkEditSummary(
					'add-both',
					siteId,
					testTitle,
					testBadges,
					testComment
				)
			);
		} );

		it( 'can add a sitelink without badges (edit metadata ommited)', async () => {
			await newRemoveSitelinkRequestBuilder( testItemId, siteId ).assertValidRequest().makeRequest();

			const testTitle = utils.title( 'test-title-' );

			const newSitelink = { title: testTitle };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, newSitelink )
				.assertValidRequest()
				.makeRequest();

			assertValidResponse( response, 201, newSitelink.title, [] );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.strictEqual(
				editMetadata.comment,
				formatSitelinkEditSummary(
					'add',
					siteId,
					testTitle
				)
			);
		} );

		it( 'can replace a sitelink with badges and edit metadata', async () => {
			const testTitle = utils.title( 'test-title-' );
			const testBadges = [ ALLOWED_BADGES[ 0 ] ];
			const testComment = 'omg – i replaced a sitelink!';

			const newSitelink = { title: testTitle, badges: testBadges };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, newSitelink )
				.withJsonBodyParam( 'comment', testComment )
				.assertValidRequest()
				.makeRequest();

			assertValidResponse( response, 200, newSitelink.title, newSitelink.badges );
			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.strictEqual(
				editMetadata.comment,
				formatSitelinkEditSummary(
					'set-both',
					siteId,
					testTitle,
					testBadges,
					testComment
				)
			);
		} );

		it( 'can replace a sitelink without badges (edit metadata omitted)', async () => {
			const testTitle = utils.title( 'test-title-' );

			const newSitelink = { title: testTitle };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, newSitelink )
				.assertValidRequest()
				.makeRequest();

			assertValidResponse( response, 200, newSitelink.title, [] );
			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.strictEqual(
				editMetadata.comment,
				formatSitelinkEditSummary(
					'set',
					siteId,
					testTitle
				)
			);

		} );

		it( 'can add/replace only the badges of a sitelink with edit metadata', async () => {
			const testTitle = utils.title( 'test-title-' );
			const testBadges = [ ALLOWED_BADGES[ 0 ] ];
			const testComment = "omg – i changed a sitelink's badges!";

			await newSetSitelinkRequestBuilder( testItemId, siteId, { title: testTitle } )
				.makeRequest();

			const sitelinkWithChangedBadges = { title: testTitle, badges: testBadges };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, sitelinkWithChangedBadges )
				.withJsonBodyParam( 'comment', testComment )
				.assertValidRequest()
				.makeRequest();

			assertValidResponse( response, 200, sitelinkWithChangedBadges.title, sitelinkWithChangedBadges.badges );
			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.strictEqual(
				editMetadata.comment,
				formatSitelinkEditSummary(
					'set-badges',
					siteId,
					null,
					testBadges,
					testComment
				)
			);
		} );

		it( 'idempotency check: can set the same sitelink twice', async () => {
			const newSitelink = { title: utils.title( 'test-title-' ), badges: [ ALLOWED_BADGES[ 1 ] ] };
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

			assertValidErrorResponse( response, 'invalid-item-id' );
			assert.include( response.body.message, invalidItemId );
		} );

		it( 'invalid site ID', async () => {
			const invalidSiteId = 'not-a-valid-site-id';
			const response = await newSetSitelinkRequestBuilder( testItemId, invalidSiteId, testSitelink )
				// .assertInvalidRequest() - valid per OAS because it only checks whether it is a string
				.makeRequest();

			assertValidErrorResponse( response, 'invalid-site-id' );
			assert.include( response.body.message, invalidSiteId );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, testSitelink )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 'invalid-edit-tag' );
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

		it( 'title is empty', async () => {
			const newSitelinkWithEmptyTitle = { title: '', badges: [ ALLOWED_BADGES[ 0 ] ] };
			const response = await newSetSitelinkRequestBuilder(
				testItemId,
				siteId,
				newSitelinkWithEmptyTitle
			).makeRequest();

			expect( response ).to.have.status( 400 );
			assertValidErrorResponse( response, 'title-field-empty' );
			assert.strictEqual( response.body.message, 'Title must not be empty' );
		} );

		it( 'sitelink title field not provided', async () => {
			const newSitelinkWithTitleFieldMissing = { badges: [ ALLOWED_BADGES[ 1 ] ] };
			const response = await newSetSitelinkRequestBuilder(
				testItemId,
				siteId,
				newSitelinkWithTitleFieldMissing
			).makeRequest();

			expect( response ).to.have.status( 400 );
			assertValidErrorResponse( response, 'sitelink-data-missing-title' );
			assert.strictEqual( response.body.message, 'Mandatory sitelink title missing' );
		} );

		it( 'invalid title', async () => {
			const newSitelinkWithInvalidTitle = { title: 'invalid title%00', badges: [ ALLOWED_BADGES[ 0 ] ] };
			const response = await newSetSitelinkRequestBuilder(
				testItemId,
				siteId,
				newSitelinkWithInvalidTitle
			).makeRequest();

			expect( response ).to.have.status( 400 );
			assertValidErrorResponse( response, 'invalid-title-field' );
			assert.strictEqual( response.body.message, 'Not a valid input for title field' );
		} );

		it( 'badges is not an array', async () => {
			const sitelink = { title: utils.title( 'test-title-' ), badges: ALLOWED_BADGES[ 1 ] };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, sitelink ).makeRequest();

			expect( response ).to.have.status( 400 );
			assertValidErrorResponse( response, 'invalid-sitelink-badges-format' );
			assert.strictEqual( response.body.message, "Value of 'badges' field is not a list" );
		} );

		it( 'badge is not an item ID', async () => {
			const invalidBadge = 'P33';
			const sitelink = { title: utils.title( 'test-title-' ), badges: [ invalidBadge ] };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, sitelink ).makeRequest();

			expect( response ).to.have.status( 400 );
			assertValidErrorResponse( response, 'invalid-input-sitelink-badge' );
			assert.strictEqual( response.body.message, `Badge input is not an item ID: ${invalidBadge}` );
		} );

		it( 'not an allowed badge', async () => {
			const badge = 'Q17';
			const sitelink = { title: utils.title( 'test-title-' ), badges: [ badge ] };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, sitelink ).makeRequest();

			expect( response ).to.have.status( 400 );
			assertValidErrorResponse( response, 'item-not-a-badge' );
			assert.strictEqual(
				response.body.message,
				`Item ID provided as badge is not allowed as a badge: ${badge}`
			);
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
