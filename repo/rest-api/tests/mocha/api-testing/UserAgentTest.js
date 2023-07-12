'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createEntityWithStatements,
	createUniqueStringProperty,
	newLegacyStatementWithRandomStringValue
} = require( '../helpers/entityHelper' );
const {
	editRequestsOnItem,
	getRequestsOnItem,
	getRequestsOnProperty
} = require( '../helpers/happyPathRequestBuilders' );

function assertValid400Response( response ) {
	expect( response ).to.have.status( 400 );
	assert.strictEqual( response.body.code, 'missing-user-agent' );
	assert.include( response.body.message, 'User-Agent' );
}

describe( 'User-Agent requests', () => {

	const itemRequestInputs = {};
	const propertyRequestInputs = {};

	before( async () => {
		const propertyId = ( await createUniqueStringProperty() ).entity.id;

		const createItemResponse = await createEntityWithStatements(
			[ newLegacyStatementWithRandomStringValue( propertyId ) ],
			'item'
		);
		itemRequestInputs.stringPropertyId = propertyId;
		itemRequestInputs.itemId = createItemResponse.entity.id;
		itemRequestInputs.statementId = createItemResponse.entity.claims[ propertyId ][ 0 ].id;

		const createPropertyResponse = await createEntityWithStatements(
			[ newLegacyStatementWithRandomStringValue( propertyId ) ],
			'property'
		);
		propertyRequestInputs.stringPropertyId = createPropertyResponse.entity.id;
		propertyRequestInputs.statementId = createPropertyResponse.entity.claims[ propertyId ][ 0 ].id;
	} );

	const useRequestInputs = ( requestInputs ) => ( newReqBuilder ) => () => newReqBuilder( requestInputs );

	[
		...getRequestsOnItem.map( useRequestInputs( itemRequestInputs ) ),
		...getRequestsOnProperty.map( useRequestInputs( propertyRequestInputs ) ),
		...editRequestsOnItem.map( useRequestInputs( itemRequestInputs ) )
	].forEach( ( newRequestBuilder ) => {
		describe( newRequestBuilder().getRouteDescription(), () => {

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

} );
