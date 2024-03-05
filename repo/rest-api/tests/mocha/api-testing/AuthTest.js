'use strict';

const { describeWithTestData } = require( '../helpers/describeWithTestData' );
const { assert, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	changeEntityProtectionStatus
} = require( '../helpers/entityHelper' );
const { requireExtensions } = require( '../../../../../tests/api-testing/utils' );
const {
	editRequestsOnItem,
	editRequestsOnProperty,
	getRequestsOnItem,
	getRequestsOnProperty
} = require( '../helpers/happyPathRequestBuilders' );

describeWithTestData( 'Auth', ( itemRequestInputs, propertyRequestInputs, describeEachRouteWithReset ) => {
	let user;

	// eslint-disable-next-line mocha/no-top-level-hooks
	before( async () => {
		user = await action.mindy();
	} );

	const useRequestInputs = ( requestInputs ) => ( newReqBuilder ) => ( {
		newRequestBuilder: () => newReqBuilder( requestInputs ),
		requestInputs
	} );

	const editRequestsWithInputs = [
		...editRequestsOnItem.map( useRequestInputs( itemRequestInputs ) ),
		...editRequestsOnProperty.map( useRequestInputs( propertyRequestInputs ) )
	];

	const allRoutes = [
		...editRequestsWithInputs,
		...getRequestsOnItem.map( useRequestInputs( itemRequestInputs ) ),
		...getRequestsOnProperty.map( useRequestInputs( propertyRequestInputs ) )
	];

	describe( 'Authentication', () => {
		describeEachRouteWithReset( allRoutes, ( newRequestBuilder ) => {
			it( 'has an X-Authenticated-User header with the logged in user', async () => {
				const response = await newRequestBuilder().withUser( user ).makeRequest();

				expect( response ).status.to.be.within( 200, 299 );
				assert.header( response, 'X-Authenticated-User', user.username );
			} );

			// eslint-disable-next-line mocha/no-skipped-tests
			describe.skip( 'OAuth', () => { // Skipping due to apache auth header issues. See T305709
				before( requireExtensions( [ 'OAuth' ] ) );

				it( 'responds with an error given an invalid bearer token', async () => {
					const response = newRequestBuilder()
						.withHeader( 'Authorization', 'Bearer this-is-an-invalid-token' )
						.makeRequest();

					expect( response ).to.have.status( 403 );
				} );
			} );
		} );
	} );
	describe( 'Authorization', () => {
		function assertPermissionDenied( response ) {
			expect( response ).to.have.status( 403 );
			assert.strictEqual( response.body.httpCode, 403 );
			assert.strictEqual( response.body.httpReason, 'Forbidden' );
			assert.strictEqual( response.body.error, 'rest-write-denied' );
		}

		describeEachRouteWithReset( editRequestsWithInputs, ( newRequestBuilder ) => {
			it( 'Unauthorized bot edit', async () => {
				assertPermissionDenied(
					await newRequestBuilder().withJsonBodyParam( 'bot', true ).makeRequest()
				);
			} );
		} );

		describeEachRouteWithReset( editRequestsWithInputs, ( newRequestBuilder ) => {
			describe( 'Blocked user', () => {
				before( async () => {
					await user.action( 'block', {
						user: user.username,
						reason: 'testing',
						token: await user.token()
					}, 'POST' );
				} );

				after( async () => {
					await user.action( 'unblock', {
						user: user.username,
						token: await user.token()
					}, 'POST' );
				} );

				it( 'cannot edit if blocked', async () => {
					const response = await newRequestBuilder().withUser( user ).makeRequest();
					expect( response ).to.have.status( 403 );
				} );
			} );
		} );

		// protecting/unprotecting does not always take effect immediately. These tests are isolated here to avoid
		// accidentally testing against a protected page in the other tests and receiving false positive results.
		editRequestsWithInputs.forEach( ( { newRequestBuilder, requestInputs } ) => {
			describe( `Protected entity page - ${newRequestBuilder().getRouteDescription()}`, () => {
				before( async () => {
					await changeEntityProtectionStatus( requestInputs.mainTestSubject, 'sysop' ); // protect
				} );

				after( async () => {
					await changeEntityProtectionStatus( requestInputs.mainTestSubject, 'all' ); // unprotect
				} );

				it( `Permission denied - ${newRequestBuilder().getRouteDescription()}`, async function () {
					// this test often hits a race condition where this request is made before the entity is protected
					this.retries( 3 );

					assertPermissionDenied( await newRequestBuilder().makeRequest() );
				} );
			} );
		} );
	} );
} );
