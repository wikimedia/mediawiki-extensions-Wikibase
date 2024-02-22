'use strict';

const { action, assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newSetSitelinkRequestBuilder,
	newRemoveSitelinkRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { formatSitelinkEditSummary } = require( '../helpers/formatEditSummaries' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { createEntity, getLocalSiteId, createWikiPage } = require( '../helpers/entityHelper' );
const { getAllowedBadges } = require( '../helpers/getAllowedBadges' );

describe( newSetSitelinkRequestBuilder().getRouteDescription(), () => {
	let testItemId;
	let siteId;
	const testTitle1 = utils.title( 'Sitelink-test-article1-' );
	const testTitle2 = utils.title( 'Sitelink-test-article2-' );
	let originalLastModified;
	let originalRevisionId;
	let allowedBadges;

	function assertValidSuccessResponse( response, status, title, badges ) {
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

		siteId = await getLocalSiteId();
		allowedBadges = await getAllowedBadges();

		await createWikiPage( testTitle1, 'sitelink test' );
		await createWikiPage( testTitle2, 'sitelink test' );

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		// wait 1s before next test to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '201', () => {
		afterEach( async () => {
			await newRemoveSitelinkRequestBuilder( testItemId, siteId ).assertValidRequest().makeRequest();
		} );

		it( 'can add a sitelink with badges and edit metadata', async () => {
			const badges = [ allowedBadges[ 0 ], allowedBadges[ 1 ] ];
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const comment = 'omg – i created a sitelink!';

			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, { title: testTitle1, badges } )
				.withJsonBodyParam( 'comment', comment )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValidSuccessResponse( response, 201, testTitle1, badges );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.strictEqual(
				editMetadata.comment,
				formatSitelinkEditSummary(
					'add-both',
					siteId,
					testTitle1,
					badges,
					comment
				)
			);
		} );

		it( 'can add a sitelink without badges (edit metadata omitted)', async () => {
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, { title: testTitle1 } )
				.assertValidRequest()
				.makeRequest();

			assertValidSuccessResponse( response, 201, testTitle1, [] );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.strictEqual(
				editMetadata.comment,
				formatSitelinkEditSummary(
					'add',
					siteId,
					testTitle1
				)
			);
		} );
	} );

	describe( '200', () => {
		beforeEach( async () => {
			await newSetSitelinkRequestBuilder( testItemId, siteId, { title: testTitle1 } ).makeRequest();
		} );

		it( 'can replace a sitelink with badges (edit metadata omitted)', async () => {
			const badges = [ allowedBadges[ 0 ] ];
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, { title: testTitle2, badges } )
				.assertValidRequest()
				.makeRequest();

			assertValidSuccessResponse( response, 200, testTitle2, badges );
			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.strictEqual(
				editMetadata.comment,
				formatSitelinkEditSummary(
					'set-both',
					siteId,
					testTitle2,
					badges
				)
			);
		} );

		it( 'can replace a sitelink without badges (edit metadata omitted)', async () => {
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, { title: testTitle2 } )
				.assertValidRequest()
				.makeRequest();

			assertValidSuccessResponse( response, 200, testTitle2, [] );
			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.strictEqual(
				editMetadata.comment,
				formatSitelinkEditSummary(
					'set',
					siteId,
					testTitle2
				)
			);
		} );

		it( 'can add/replace only the badges of a sitelink (edit metadata omitted)', async () => {
			const badges = [ allowedBadges[ 0 ] ];
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, { title: testTitle1, badges } )
				.assertValidRequest()
				.makeRequest();

			assertValidSuccessResponse( response, 200, testTitle1, badges );
			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.strictEqual(
				editMetadata.comment,
				formatSitelinkEditSummary(
					'set-badges',
					siteId,
					null,
					badges
				)
			);
		} );

		it( 'idempotency check: can set the same sitelink twice', async () => {
			const newSitelink = { title: testTitle2, badges: [ allowedBadges[ 1 ] ] };
			const reqBuilder = await newSetSitelinkRequestBuilder( testItemId, siteId, newSitelink )
				.assertValidRequest();

			assertValidSuccessResponse( await reqBuilder.makeRequest(), 200, newSitelink.title, newSitelink.badges );
			assertValidSuccessResponse( await reqBuilder.makeRequest(), 200, newSitelink.title, newSitelink.badges );
		} );

		describe( 'sitelinks to redirects', () => {
			const redirectTitle = utils.title( 'Redirect-title-' );
			before( async () => {
				await createWikiPage( redirectTitle, `#REDIRECT [[${testTitle1}]]` );
			} );

			it( 'resolves title redirects without a redirect badge', async () => {
				const response = await newSetSitelinkRequestBuilder( testItemId, siteId, { title: redirectTitle } )
					.assertValidRequest()
					.makeRequest();

				assertValidSuccessResponse( response, 200, testTitle1, [] );
			} );

			it( 'does not resolve redirects if the sitelink contains a redirect badge', async () => {
				const redirectBadge = allowedBadges[ 1 ];
				const response = await newSetSitelinkRequestBuilder(
					testItemId,
					siteId,
					{ title: redirectTitle, badges: [ redirectBadge ] }
				)
					.withHeader( 'X-Wikibase-CI-Redirect-Badges', redirectBadge )
					.assertValidRequest()
					.makeRequest();

				assertValidSuccessResponse( response, 200, redirectTitle, [ redirectBadge ] );
			} );
		} );
	} );

	describe( '400', () => {
		it( 'invalid item ID', async () => {
			const invalidItemId = 'X123';
			const response = await newSetSitelinkRequestBuilder( invalidItemId, siteId, { title: testTitle1 } )
				.assertInvalidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 'invalid-item-id' );
			assert.include( response.body.message, invalidItemId );
		} );

		it( 'invalid site ID', async () => {
			const invalidSiteId = 'not-a-valid-site-id';
			const response = await newSetSitelinkRequestBuilder( testItemId, invalidSiteId, { title: testTitle1 } )
				// .assertInvalidRequest() - valid per OAS because it only checks whether it is a string
				.makeRequest();

			assertValidErrorResponse( response, 'invalid-site-id' );
			assert.include( response.body.message, invalidSiteId );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, { title: testTitle1 } )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, { title: testTitle1 } )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, { title: testTitle1 } )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, { title: testTitle1 } )
				.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, { title: testTitle1 } )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'comment' );
			assert.strictEqual( response.body.expectedType, 'string' );
		} );

		it( 'title is empty', async () => {
			const newSitelinkWithEmptyTitle = { title: '' };
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
			const newSitelinkWithTitleFieldMissing = { badges: [ allowedBadges[ 1 ] ] };
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
			const newSitelinkWithInvalidTitle = { title: 'invalid title%00' };
			const response = await newSetSitelinkRequestBuilder(
				testItemId,
				siteId,
				newSitelinkWithInvalidTitle
			).makeRequest();

			expect( response ).to.have.status( 400 );
			assertValidErrorResponse( response, 'invalid-title-field' );
			assert.strictEqual( response.body.message, 'Not a valid input for title field' );
		} );

		it( 'title is not a string', async () => {
			const newSitelinkWithInvalidTitle = { title: [ 'array', 'not', 'allowed' ] };
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
			const sitelink = { title: utils.title( 'test-title-' ), badges: allowedBadges[ 1 ] };
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
			const badge = testItemId;
			const sitelink = { title: utils.title( 'test-title-' ), badges: [ badge ] };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, sitelink ).makeRequest();

			expect( response ).to.have.status( 400 );
			assertValidErrorResponse( response, 'item-not-a-badge' );
			assert.strictEqual(
				response.body.message,
				`Item ID provided as badge is not allowed as a badge: ${badge}`
			);
		} );

		it( 'sitelink title does not exist', async () => {
			const sitelink = { title: utils.title( 'does-not-exist-' ) };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, sitelink ).makeRequest();

			expect( response ).to.have.status( 400 );
			assertValidErrorResponse( response, 'title-does-not-exist' );
			assert.strictEqual(
				response.body.message,
				`Page with title ${sitelink.title} does not exist on the given site`
			);
		} );
	} );

	describe( '404', () => {
		it( 'item not found', async () => {
			const itemId = 'Q999999';
			const response = await newSetSitelinkRequestBuilder( itemId, siteId, { title: testTitle1 } )
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

			const response = await newSetSitelinkRequestBuilder( redirectSource, siteId, { title: testTitle1 } )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 409 );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
			assert.strictEqual( response.body.code, 'redirected-item' );
		} );
	} );
} );
