'use strict';

const { describeWithTestData } = require( '../helpers/describeWithTestData' );
const { assert } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { RequestBuilder } = require( '../../../../../rest-api/tests/mocha/helpers/RequestBuilder' );
const {
	getItemGetRequests,
	getPropertyGetRequests,
	getItemEditRequests,
	getPropertyEditRequests,
	getItemCreateRequest
} = require( '../helpers/happyPathRequestBuilders' );

describeWithTestData( 'V0Error', ( itemRequestInputs, propertyRequestInputs, describeEachRouteWithReset ) => {

	const adminRequests = [
		{ newRequestBuilder: () => new RequestBuilder().withRoute( 'GET', '/v1/property-data-types' ) },
		{ newRequestBuilder: () => new RequestBuilder().withRoute( 'GET', '/v1/openapi.json' ) }
	];

	const editRequests = [
		...getItemEditRequests( itemRequestInputs ),
		...getPropertyEditRequests( propertyRequestInputs )
	];
	const allRoutes = [
		...adminRequests,
		...editRequests,
		...getItemGetRequests( itemRequestInputs ),
		...getPropertyGetRequests( propertyRequestInputs ),
		getItemCreateRequest( itemRequestInputs )
	];

	describe( 'v0 error', () => {
		describeEachRouteWithReset( allRoutes, ( newRequestBuilder ) => {
			it( 'responds with v0-has-been-removed error', async () => {
				const builder = newRequestBuilder();
				const response = await builder
					.withRoute( builder.method, builder.route.replace( 'v1', 'v0' ) )
					.skipRouteValidation()
					.makeRequest();

				expect( response ).to.have.status( 404 );
				assert.strictEqual( response.body.code, 'resource-not-found' );
				assert.strictEqual(
					response.body.message,
					'v0 has been removed, please modify your routes to v1 such as \'/rest.php/wikibase/v1\''
				);
			} );

		} );
	} );
} );
