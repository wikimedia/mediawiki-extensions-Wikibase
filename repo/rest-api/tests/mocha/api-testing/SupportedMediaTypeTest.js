'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const {
	getItemEditRequests,
	getPropertyEditRequests,
	getItemCreateRequest,
	getPropertyCreateRequest
} = require( '../helpers/happyPathRequestBuilders' );
const { describeWithTestData } = require( '../helpers/describeWithTestData' );

describeWithTestData(
	'supported media type requests',
	( itemRequestInputs, propertyRequestInputs, describeEachRouteWithReset ) => {
		const requestsToTest = [
			...getItemEditRequests( itemRequestInputs ),
			...getPropertyEditRequests( propertyRequestInputs ),
			getItemCreateRequest( itemRequestInputs ),
			getPropertyCreateRequest( propertyRequestInputs )
		];
		describeEachRouteWithReset( requestsToTest, ( newRequestBuilder ) => {
			// We implicitly check that edit routes support application/json
			// So no need to add additional test for it.

			// Accept DELETE endpoints with no content-type
			if ( newRequestBuilder().getMethod() === 'DELETE' ) {
				it( `${newRequestBuilder().getRouteDescription()} responds OK with no content type`,
					async () => {
						const response = await newRequestBuilder().assertValidRequest().makeRequest();
						expect( response.status ).to.be.within( 200, 299 );
					}
				);
			}

			// Accept 'application/json-patch+json' content-type for PATCH endpoints
			if ( newRequestBuilder().getMethod() === 'PATCH' ) {
				it( `${newRequestBuilder().getRouteDescription()} responds OK for application/json-patch+json`, async () => {
					const contentType = 'application/json-patch+json';
					const response = await newRequestBuilder()
						.withHeader( 'content-type', contentType )
						.assertValidRequest()
						.makeRequest();

					expect( response.status ).to.be.within( 200, 299 );
				} );
			}
		} );
	}
);
