'use strict';

const { describeWithTestData } = require( '../helpers/describeWithTestData' );
const { action } = require( 'api-testing' );
const {
	getItemEditRequests,
	getPropertyEditRequests,
	getItemCreateRequest
} = require( '../helpers/happyPathRequestBuilders' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );

describeWithTestData( '403 for all edit routes', ( itemRequestInputs, propertyRequestInputs ) => {
	let blockedUser;

	// eslint-disable-next-line mocha/no-top-level-hooks
	before( async () => {
		blockedUser = await action.blockedUser();
	} );

	[
		...getItemEditRequests( itemRequestInputs ),
		...getPropertyEditRequests( propertyRequestInputs ),
		getItemCreateRequest( itemRequestInputs )
	].forEach( ( { newRequestBuilder } ) => {
		it( `${newRequestBuilder().getRouteDescription()} responds with a valid 403 response`, async () => {
			const response = await newRequestBuilder()
				.withUser( blockedUser )
				.makeRequest();

			expect( response ).to.have.status( 403 );
			expect( response ).to.satisfyApiSchema;
		} );
	} );
} );
