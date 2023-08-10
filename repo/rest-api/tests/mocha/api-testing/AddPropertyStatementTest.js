'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newAddPropertyStatementRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newAddPropertyStatementRequestBuilder().getRouteDescription(), () => {
	let testPropertyId;
	let testStatement;

	function assertValid201Response( response, propertyId = null, valueContent = null ) {
		expect( response ).to.have.status( 201 );
		assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		assert.strictEqual( response.body.property.id, propertyId || testStatement.property.id );
		assert.deepStrictEqual( response.body.value.content, valueContent || testStatement.value.content );
	}

	before( async () => {
		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
		testStatement = entityHelper.newStatementWithRandomStringValue( testPropertyId );
	} );

	describe( '201 success response ', () => {
		it( 'can add a statement to a property', async () => {
			const response = await newAddPropertyStatementRequestBuilder( testPropertyId, testStatement )
				.assertValidRequest().makeRequest();
			assertValid201Response(
				response,
				testPropertyId,
				testStatement.value.content
			);
		} );
	} );

} );
