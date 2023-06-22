'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createItemWithStatements,
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

	let requestInputs = {};

	before( async () => {
		const stringPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const createEntityResponse = await createItemWithStatements( [
			newLegacyStatementWithRandomStringValue( stringPropertyId )
		] );
		const itemId = createEntityResponse.entity.id;
		const statementId = createEntityResponse.entity.claims[ stringPropertyId ][ 0 ].id;

		requestInputs = { itemId, statementId, stringPropertyId };
	} );

	[
		...getRequestsOnItem,
		...getRequestsOnProperty,
		...editRequestsOnItem
	].forEach( ( newRequestBuilder ) => {
		describe( newRequestBuilder( requestInputs ).getRouteDescription(), () => {

			it( 'No User-Agent header provided', async () => {
				const requestBuilder = newRequestBuilder( requestInputs );
				delete requestBuilder.headers[ 'user-agent' ];
				const response = await requestBuilder
					.assertValidRequest()
					.makeRequest();

				assertValid400Response( response );
			} );

			it( 'Empty User-Agent header provided', async () => {
				const response = await newRequestBuilder( requestInputs )
					.withHeader( 'user-agent', '' )
					.assertValidRequest()
					.makeRequest();

				assertValid400Response( response );
			} );

		} );
	} );

} );
