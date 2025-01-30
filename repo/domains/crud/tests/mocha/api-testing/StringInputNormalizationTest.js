'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { newCreateItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( 'String input normalization', () => {

	it( 'should normalize string inputs to NFC', async () => {
		const response = await newCreateItemRequestBuilder(
			{ labels: { de: 'K\u0061\u0308se' } }
		).assertValidRequest().makeRequest();

		expect( response ).to.have.status( 201 );
		assert.deepEqual( response.body.labels.de, 'Käse' );

	} );
} );
