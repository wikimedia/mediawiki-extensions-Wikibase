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
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );

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
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
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

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'item_id' }
			);
		} );

		it( 'invalid site ID', async () => {
			const invalidSiteId = 'not-a-valid-site-id';
			const response = await newSetSitelinkRequestBuilder( testItemId, invalidSiteId, { title: testTitle1 } )
				// .assertInvalidRequest() - valid per OAS because it only checks whether it is a string
				.makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'site_id' }
			);
		} );

		it( 'missing top-level field', async () => {
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, {} )
				.withEmptyJsonBody()
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'missing-field' );
			assert.deepEqual( response.body.context, { path: '/', field: 'sitelink' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( 'title is empty', async () => {
			const newSitelinkWithEmptyTitle = { title: '' };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, newSitelinkWithEmptyTitle )
				.makeRequest();

			const path = '/sitelink/title';

			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.include( response.body.message, path );
		} );

		it( 'sitelink title field not provided', async () => {
			const newSitelinkWithTitleFieldMissing = { badges: [ allowedBadges[ 1 ] ] };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, newSitelinkWithTitleFieldMissing )
				.makeRequest();

			assertValidError( response, 400, 'missing-field', { path: '/sitelink', field: 'title' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( 'invalid title', async () => {
			const newSitelinkWithInvalidTitle = { title: 'invalid title%00' };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, newSitelinkWithInvalidTitle )
				.makeRequest();
			const path = '/sitelink/title';

			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.include( response.body.message, path );
		} );

		it( 'title is not a string', async () => {
			const newSitelinkWithInvalidTitle = { title: [ 'array', 'not', 'allowed' ] };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, newSitelinkWithInvalidTitle )
				.makeRequest();
			const path = '/sitelink/title';

			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.include( response.body.message, path );
		} );

		it( 'badges is not an array', async () => {
			const sitelink = { title: testTitle1, badges: allowedBadges[ 1 ] };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, sitelink ).makeRequest();

			const path = '/sitelink/badges';
			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.strictEqual( response.body.message, `Invalid value at '${path}'` );
		} );

		it( 'badge is not an item ID', async () => {
			const invalidBadge = 'P33';
			const sitelink = { title: testTitle1, badges: [ invalidBadge ] };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, sitelink ).makeRequest();

			const path = '/sitelink/badges/0';
			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.strictEqual( response.body.message, `Invalid value at '${path}'` );
		} );

		it( 'not an allowed badge', async () => {
			const sitelink = { title: testTitle1, badges: [ testItemId ] };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, sitelink ).makeRequest();

			const path = '/sitelink/badges/0';
			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.strictEqual( response.body.message, `Invalid value at '${path}'` );
		} );

		it( 'badge item does not exist', async () => {
			const badge = 'Q99999999';
			const sitelink = { title: testTitle1, badges: [ badge ] };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, sitelink )
				.withHeader( 'X-Wikibase-CI-Badges', badge )
				.makeRequest();

			const path = '/sitelink/badges/0';
			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.strictEqual( response.body.message, `Invalid value at '${path}'` );
		} );

		it( 'sitelink title does not exist', async () => {
			const sitelink = { title: utils.title( 'does-not-exist-' ) };
			const response = await newSetSitelinkRequestBuilder( testItemId, siteId, sitelink ).makeRequest();

			assertValidError( response, 400, 'title-does-not-exist' );
			assert.strictEqual(
				response.body.message,
				`Page with title ${sitelink.title} does not exist on the given site`
			);
		} );
	} );

	describe( '404', () => {
		it( 'item not found', async () => {
			const itemId = 'Q999999';
			const response = await newSetSitelinkRequestBuilder( itemId, siteId, { title: testTitle2 } )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'item' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );
	} );

	describe( '409', () => {
		it( 'item is a redirect', async () => {
			const redirectTarget = testItemId;
			const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

			const response = await newSetSitelinkRequestBuilder( redirectSource, siteId, { title: testTitle2 } )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 409, 'redirected-item', { redirect_target: redirectTarget } );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
		} );
	} );

	describe( '422', () => {
		it( 'sitelink conflict', async () => {
			await newSetSitelinkRequestBuilder( testItemId, siteId, { title: testTitle1 } )
				.assertValidRequest()
				.makeRequest();

			const newItem = await createEntity( 'item', {} );
			const response = await newSetSitelinkRequestBuilder( newItem.entity.id, siteId, { title: testTitle1 } )
				.assertValidRequest()
				.makeRequest();

			const context = {
				violation: 'sitelink-conflict',
				violation_context: { conflicting_item_id: testItemId }
			};

			assertValidError( response, 422, 'data-policy-violation', context );
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
		} );
	} );
} );
