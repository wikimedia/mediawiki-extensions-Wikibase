'use strict';

const { describeWithTestData } = require( '../helpers/describeWithTestData' );
const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	getItemEditRequests,
	getPropertyEditRequests,
	getItemCreateRequest
} = require( '../helpers/happyPathRequestBuilders' );
const entityHelper = require( '../helpers/entityHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );

describeWithTestData( 'IP masking', ( itemRequestInputs, propertyRequestInputs, describeEachRouteWithReset ) => {
	function withTempUserConfig( newRequestBuilder, config ) {
		return newRequestBuilder().withHeader( 'X-Wikibase-Ci-Tempuser-Config', JSON.stringify( config ) );
	}

	const editRequests = [
		...getItemEditRequests( itemRequestInputs ),
		...getPropertyEditRequests( propertyRequestInputs )
	];

	describeEachRouteWithReset( editRequests, ( newRequestBuilder, requestInputs ) => {
		it( 'makes an edit as an IP user with tempUser disabled', async () => {
			const response = await withTempUserConfig( newRequestBuilder, { enabled: false } )
				.makeRequest();

			expect( response ).status.to.be.within( 200, 299 );
			const { user } = await entityHelper.getLatestEditMetadata( requestInputs.mainTestSubject );
			assert.match( user, /^\d+\.\d+\.\d+\.\d+$/ );
		} );

		it( 'makes an edit as a temp user with tempUser enabled', async () => {
			const tempUserPrefix = 'TempUserTest';
			const response = await withTempUserConfig(
				newRequestBuilder,
				{ enabled: true, genPattern: `${tempUserPrefix} $1` }
			).makeRequest();

			expect( response ).status.to.be.within( 200, 299 );
			const { user } = await entityHelper.getLatestEditMetadata( requestInputs.mainTestSubject );
			assert.include( user, tempUserPrefix );
		} );

		// Note: If this test fails, it might be due to the throttler relying on caching.
		// Ensure caching is enabled for the wiki under test, as the throttler won't work without it.
		it( 'responds 429 when the temp user creation limit is reached', async () => {
			await newRequestBuilder()
				.withHeader( 'X-Wikibase-Ci-Tempuser-Config', JSON.stringify( { enabled: true } ) )
				.withHeader( 'X-Wikibase-CI-Temp-Account-Limit-One', true )
				.makeRequest();

			const response = await newRequestBuilder()
				.withHeader( 'X-Wikibase-Ci-Tempuser-Config', JSON.stringify( { enabled: true } ) )
				.withHeader( 'X-Wikibase-CI-Temp-Account-Limit-One', true )
				.makeRequest();

			assertValidError(
				response,
				429,
				'request-limit-reached',
				{ reason: 'temp-account-creation-limit-reached' }
			);
		} );
	} );

	// checking the latest metadata for the newly created item
	describeEachRouteWithReset( [ getItemCreateRequest( itemRequestInputs ) ], ( newRequestBuilder ) => {
		it( 'makes an item create as an IP user with tempUser disabled', async () => {
			const response = await withTempUserConfig( newRequestBuilder, { enabled: false } )
				.makeRequest();

			expect( response ).status.to.be.within( 200, 299 );
			const { user } = await entityHelper.getLatestEditMetadata( response.body.id );
			assert.match( user, /^\d+\.\d+\.\d+\.\d+$/ );
		} );

		it( 'makes an item create as a temp user with tempUser enabled', async () => {
			const tempUserPrefix = 'TempUserTest';
			const response = await withTempUserConfig(
				newRequestBuilder,
				{ enabled: true, genPattern: `${tempUserPrefix} $1` }
			).makeRequest();

			expect( response ).status.to.be.within( 200, 299 );
			const { user } = await entityHelper.getLatestEditMetadata( response.body.id );
			assert.include( user, tempUserPrefix );
		} );
	} );
} );
