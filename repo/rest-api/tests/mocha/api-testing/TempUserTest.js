'use strict';

const { describeWithTestData } = require( '../helpers/describeWithTestData' );
const { action, assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	getItemEditRequests,
	getPropertyEditRequests,
	getItemCreateRequest
} = require( '../helpers/happyPathRequestBuilders' );
const entityHelper = require( '../helpers/entityHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { newSetItemLabelRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describeWithTestData( 'IP masking', ( itemRequestInputs, propertyRequestInputs, describeEachRouteWithReset ) => {
	const tempUserPrefix = 'TempUserTest';

	function withTempUsersEnabled( requestBuilder ) {
		return requestBuilder.withConfigOverride( 'wgAutoCreateTempUser', {
			enabled: true,
			genPattern: `${tempUserPrefix} $1`
		} );
	}

	const createItemRequest = getItemCreateRequest( itemRequestInputs );
	const requests = [
		createItemRequest,
		...getItemEditRequests( itemRequestInputs ),
		...getPropertyEditRequests( propertyRequestInputs )
	];

	describeEachRouteWithReset( requests, ( newRequestBuilder, requestInputs ) => {
		it( 'makes an edit as an IP user with tempUser disabled', async () => {
			const response = await newRequestBuilder()
				.withConfigOverride( 'wgAutoCreateTempUser', { enabled: false } )
				.makeRequest();

			expect( response ).status.to.be.within( 200, 299 );
			const { user } = await entityHelper.getLatestEditMetadata(
				newRequestBuilder === createItemRequest.newRequestBuilder ? response.body.id : requestInputs.mainTestSubject
			);

			assert.match( user, /^\d+\.\d+\.\d+\.\d+$/ );
		} );

		describe( 'temp user creation', () => {
			it( 'makes an edit as a temp user with tempUser enabled', async () => {
				const response = await withTempUsersEnabled( newRequestBuilder() ).makeRequest();

				expect( response ).status.to.be.within( 200, 299 );
				const { user } = await entityHelper.getLatestEditMetadata(
					newRequestBuilder === createItemRequest.newRequestBuilder ? response.body.id : requestInputs.mainTestSubject
				);
				assert.include( user, tempUserPrefix );
				assert.header( response, 'X-Temporary-User-Created', user );
			} );

			// Note: If this test fails, it might be due to the throttler relying on caching.
			// Ensure caching is enabled for the wiki under test, as the throttler won't work without it.
			it( 'responds 429 when the temp user creation limit is reached', async () => {
				const requestBuilder = withTempUsersEnabled( newRequestBuilder() )
					.withConfigOverride( 'wgTempAccountCreationThrottle', [ { count: 1, seconds: 86400 } ] );

				await requestBuilder.makeRequest();
				const response = await requestBuilder.makeRequest();

				assertValidError(
					response,
					429,
					'request-limit-reached',
					{ reason: 'temp-account-creation-limit-reached' }
				);
			} );
		} );

		describe( 'temp user authentication', () => {
			let existingTempUserName;
			let userSession;

			before( async () => {
				userSession = await action.getAnon();
				// Any edit works here. We just need an existing temp user for the actual test.
				const initialEdit = await withTempUsersEnabled( newSetItemLabelRequestBuilder(
					itemRequestInputs.itemId,
					'en',
					utils.title( 'some-label-' )
				) ).withUser( userSession ).makeRequest();

				expect( initialEdit ).status.to.be.within( 200, 299 );
				const editMeta = await entityHelper.getLatestEditMetadata( itemRequestInputs.itemId );
				existingTempUserName = editMeta.user;
			} );

			it( 'can authenticate as the temp user after the creation', async () => {
				const response = await withTempUsersEnabled( newRequestBuilder() )
					.withUser( userSession )
					.makeRequest();

				expect( response ).status.to.be.within( 200, 299 );
				const { user } = await entityHelper.getLatestEditMetadata(
					newRequestBuilder === createItemRequest.newRequestBuilder ? response.body.id : requestInputs.mainTestSubject
				);
				assert.include( user, tempUserPrefix );
				assert.strictEqual( user, existingTempUserName );
				assert.header( response, 'X-Authenticated-User', undefined );
				assert.header( response, 'X-Temporary-User-Created', undefined );
			} );
		} );
	} );
} );
