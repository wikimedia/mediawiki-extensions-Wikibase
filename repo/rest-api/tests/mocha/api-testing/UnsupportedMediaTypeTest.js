'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	editRequestsOnItem,
	editRequestsOnProperty
} = require( '../helpers/happyPathRequestBuilders' );
const { describeWithTestData } = require( '../helpers/describeWithTestData' );

describeWithTestData(
	'Unsupported media type requests',
	( itemRequestInputs, propertyRequestInputs, describeEachRouteWithReset ) => {
		const useRequestInputs = ( requestInputs ) => ( newReqBuilder ) => ( {
			newRequestBuilder: () => newReqBuilder( requestInputs ),
			requestInputs
		} );

		const editRequestsWithInputs = [
			...editRequestsOnItem.map( useRequestInputs( itemRequestInputs ) ),
			...editRequestsOnProperty.map( useRequestInputs( propertyRequestInputs ) )
		];

		describeEachRouteWithReset( editRequestsWithInputs, ( newRequestBuilder ) => {
			it( `${newRequestBuilder().getRouteDescription()} responds 415 for an unsupported media type`, async () => {
				const contentType = 'multipart/form-data';
				const response = await newRequestBuilder()
					.withHeader( 'content-type', contentType )
					.assertInvalidRequest()
					.makeRequest();

				expect( response ).to.have.status( 415 );
				assert.strictEqual( response.body.message, `Unsupported Content-Type: '${contentType}'` );
			} );
		} );
	}
);
