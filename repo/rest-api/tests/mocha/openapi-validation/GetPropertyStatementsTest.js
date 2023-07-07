'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const {
	createEntityWithStatements,
	createUniqueStringProperty,
	newLegacyStatementWithRandomStringValue
} = require( '../helpers/entityHelper' );
const { newGetPropertyStatementsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( 'validate GET /entities/properties/{id}/statements responses against OpenAPI spec', () => {

	let propertyId;
	let latestRevisionId;

	before( async () => {
		const createPropertyResponse = await createUniqueStringProperty();
		propertyId = createPropertyResponse.entity.id;
		latestRevisionId = createPropertyResponse.entity.lastrevid;
	} );

	it( '200 OK response is valid for an Property with no statements', async () => {
		const response = await newGetPropertyStatementsRequestBuilder( propertyId ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '200 OK response is valid for an Property with statements', async () => {
		const statementPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const { entity: { id } } = await createEntityWithStatements( [
			newLegacyStatementWithRandomStringValue( statementPropertyId ),
			newLegacyStatementWithRandomStringValue( statementPropertyId )
		], 'property' );
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
