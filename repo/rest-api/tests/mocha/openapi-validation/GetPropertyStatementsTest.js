'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const {
	createUniqueStringProperty,
	createPropertyWithStatements,
	newStatementWithRandomStringValue
} = require( '../helpers/entityHelper' );
const { newGetPropertyStatementsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetPropertyStatementsRequestBuilder().getRouteDescription(), () => {

	let propertyId;
	let latestRevisionId;

	before( async () => {
		const createPropertyResponse = await createUniqueStringProperty();
		propertyId = createPropertyResponse.entity.id;
		latestRevisionId = createPropertyResponse.entity.lastrevid;
	} );

	it( '200 OK response is valid for an Property with statements', async () => {
		const statementPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const { id } = await createPropertyWithStatements( [
			newStatementWithRandomStringValue( statementPropertyId ),
			newStatementWithRandomStringValue( statementPropertyId )
		] );
		const response = await newGetPropertyStatementsRequestBuilder( id ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetPropertyStatementsRequestBuilder( propertyId )
			.withHeader( 'If-None-Match', `"${latestRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid property ID', async () => {
		const response = await newGetPropertyStatementsRequestBuilder( 'X123' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing property', async () => {
		const response = await newGetPropertyStatementsRequestBuilder( 'P99999' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
