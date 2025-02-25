'use strict';

const { describeWithTestData } = require( '../helpers/describeWithTestData' );
const { assert, action } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { changeEntityProtectionStatus } = require( '../helpers/entityHelper' );
const { requireExtensions } = require( '../../../../../../tests/api-testing/utils' );
const {
	getItemGetRequests,
	getPropertyGetRequests,
	getItemEditRequests,
	getPropertyEditRequests,
	getItemCreateRequest,
	getPropertyCreateRequest
} = require( '../helpers/happyPathRequestBuilders' );
const { getOrCreateAuthTestUser } = require( '../helpers/testUsers' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { newCreatePropertyRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { runAllJobs } = require( 'api-testing/lib/wiki' );

describeWithTestData( 'Auth', ( itemRequestInputs, propertyRequestInputs, describeEachRouteWithReset ) => {
	let user;
	let root;

	// eslint-disable-next-line mocha/no-top-level-hooks
	before( async () => {
		// using a single-purpose user here because blocking it might interfere with other tests
		user = await getOrCreateAuthTestUser();
		root = await action.root();
	} );

	const editRequests = [
		...getItemEditRequests( itemRequestInputs ),
		...getPropertyEditRequests( propertyRequestInputs )
	];
	const allRoutes = [
		...editRequests,
		...getItemGetRequests( itemRequestInputs ),
		...getPropertyGetRequests( propertyRequestInputs ),
		getItemCreateRequest( itemRequestInputs ),
		getPropertyCreateRequest( propertyRequestInputs )
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
		describeEachRouteWithReset(
			[
				...editRequests,
				getItemCreateRequest( itemRequestInputs ),
				getPropertyCreateRequest( propertyRequestInputs )
			],
			( newRequestBuilder ) => {
				it( 'Unauthorized bot edit', async () => {
					assertValidError(
						await newRequestBuilder().withJsonBodyParam( 'bot', true ).makeRequest(),
						403,
						'permission-denied',
						{ denial_reason: 'unauthorized-bot-edit' }
					);
				} );
			}
		);

		describeEachRouteWithReset(
			[
				...editRequests,
				getItemCreateRequest( itemRequestInputs ),
				getPropertyCreateRequest( propertyRequestInputs )
			],
			( newRequestBuilder ) => {
				describe( 'Blocked user', () => {
					before( async () => {
						await root.action( 'block', {
							user: user.username,
							reason: 'testing',
							token: await root.token()
						}, 'POST' );
					} );

					after( async () => {
						await root.action( 'unblock', {
							user: user.username,
							token: await root.token()
						}, 'POST' );
					} );

					it( 'cannot create/edit if blocked', async () => {
						assertValidError(
							await newRequestBuilder().withUser( user ).makeRequest(),
							403,
							'permission-denied',
							{ denial_reason: 'blocked-user' }
						);
					} );
				} );
			}
		);

		// protecting/unprotecting does not always take effect immediately. These tests are isolated here to avoid
		// accidentally testing against a protected page in the other tests and receiving false positive results.
		editRequests.forEach( ( { newRequestBuilder, requestInputs } ) => {
			describe( `Protected entity page - ${newRequestBuilder().getRouteDescription()}`, () => {
				before( async () => {
					await changeEntityProtectionStatus( requestInputs.mainTestSubject, 'sysop' ); // protect
				} );

				after( async () => {
					await changeEntityProtectionStatus( requestInputs.mainTestSubject, 'all' ); // unprotect
					await runAllJobs();
				} );

				it( `Permission denied - ${newRequestBuilder().getRouteDescription()}`, async function () {
					// this test often hits a race condition where this request is made before the entity is protected
					this.retries( 3 );

					assertValidError(
						await newRequestBuilder().makeRequest(),
						403,
						'permission-denied',
						{ denial_reason: 'resource-protected' }
					);
				} );
			} );
		} );

		it( 'cannot create a property without the property-create permission', async () => {
			const response = await newCreatePropertyRequestBuilder( { data_type: 'string' } )
				.withUser( user )
				.withConfigOverride(
					'wgGroupPermissions',
					{ '*': { read: true, edit: true, createpage: true, 'property-create': false } }
				)
				.makeRequest();

			expect( response ).to.have.status( 403 );
		} );
	} );
}, true ); // create a new Item for the Auth tests
