'use strict';

const { describeWithTestData } = require( '../helpers/describeWithTestData' );
const { assert } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const {
	getItemEditRequests,
	getPropertyEditRequests,
	getItemCreateRequest,
	getPropertyCreateRequest
} = require( '../helpers/happyPathRequestBuilders' );
const { assertValidError } = require( '../helpers/responseValidator' );

describeWithTestData( 'Edit metadata requests', (
	itemRequestInputs,
	propertyRequestInputs,
	describeEachRouteWithReset
) => {
	const allRoutes = [
		...getItemEditRequests( itemRequestInputs ),
		...getPropertyEditRequests( propertyRequestInputs ),
		getItemCreateRequest( itemRequestInputs ),
		getPropertyCreateRequest( propertyRequestInputs )
	];
	describeEachRouteWithReset( allRoutes, ( newRequestBuilder ) => {
		it( 'comment too long', async () => {
			const response = await newRequestBuilder()
				.withJsonBodyParam( 'comment', 'x'.repeat( 501 ) )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'value-too-long', { path: '/comment', limit: 500 } );
			assert.strictEqual( response.body.message, 'The input value is too long' );
		} );

		it( 'invalid edit tag', async () => {
			const response = await newRequestBuilder()
				.withJsonBodyParam( 'tags', [ 'invalid tag' ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/tags/0' } );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newRequestBuilder()
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/tags' } );
		} );

		it( 'invalid bot flag', async () => {
			const response = await newRequestBuilder()
				.withJsonBodyParam( 'bot', 'should be a boolean' )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/bot' } );
		} );

		it( 'invalid comment', async () => {
			const response = await newRequestBuilder()
				.withJsonBodyParam( 'comment', 123 )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/comment' } );
		} );
	} );
} );
