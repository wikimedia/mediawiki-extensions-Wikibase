'use strict';

const { describeWithTestData } = require( '../helpers/describeWithTestData' );
const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	getItemGetRequests,
	getPropertyGetRequests,
	getItemEditRequests,
	getPropertyEditRequests
} = require( '../helpers/happyPathRequestBuilders' );

function assertValid400Response( response ) {
	expect( response ).to.have.status( 400 );
	assert.strictEqual( response.body.code, 'missing-user-agent' );
	assert.include( response.body.message, 'User-Agent' );
}

describeWithTestData( 'User-Agent requests', (
	itemRequestInputs,
	propertyRequestInputs,
	describeEachRouteWithReset
) => {
	const allRoutes = [
		...getItemEditRequests( itemRequestInputs ),
		...getPropertyEditRequests( propertyRequestInputs ),
		...getItemGetRequests( itemRequestInputs ),
		...getPropertyGetRequests( propertyRequestInputs )
	];
	describeEachRouteWithReset( allRoutes, ( newRequestBuilder ) => {
		it( 'No User-Agent header provided', async () => {
			const requestBuilder = newRequestBuilder();
			delete requestBuilder.headers[ 'user-agent' ];
			const response = await requestBuilder
				.assertValidRequest()
				.makeRequest();

			assertValid400Response( response );
		} );

		it( 'Empty User-Agent header provided', async () => {
			const response = await newRequestBuilder()
				.withHeader( 'user-agent', '' )
				.assertValidRequest()
				.makeRequest();

			assertValid400Response( response );
		} );
	} );
} );
