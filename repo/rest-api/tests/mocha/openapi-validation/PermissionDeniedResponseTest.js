'use strict';

const { describeWithTestData } = require( '../helpers/describeWithTestData' );
const {
	getItemEditRequests,
	getPropertyEditRequests,
	getItemCreateRequest
} = require( '../helpers/happyPathRequestBuilders' );
const { expect } = require( '../helpers/chaiHelper' );
describeWithTestData( '403 for all edit routes', ( itemRequestInputs, propertyRequestInputs ) => {
	[
		...getItemEditRequests( itemRequestInputs ),
		...getPropertyEditRequests( propertyRequestInputs ),
		getItemCreateRequest( itemRequestInputs )
	].forEach( ( { newRequestBuilder } ) => {
		it( `${newRequestBuilder().getRouteDescription()} responds with a valid 403 response`, async () => {
			const response = await newRequestBuilder()
				.withJsonBodyParam( 'bot', true )
				.makeRequest();

			expect( response ).to.have.status( 403 );
			expect( response ).to.satisfyApiSpec;
		} );
	} );
} );
