'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newGetPropertyStatementRequestBuilder,
	newGetStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( 'validate GET statement responses', () => {

	let testPropertyId;
	let testStatementId;
	let lastRevId;

	before( async () => {
		const statementPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
		const createPropertyResponse = await entityHelper.createPropertyWithStatements( [
			entityHelper.newLegacyStatementWithRandomStringValue( statementPropertyId )
		] );
		testPropertyId = createPropertyResponse.entity.id;
		testStatementId = Object.values( createPropertyResponse.entity.claims )[ 0 ][ 0 ].id;
		lastRevId = createPropertyResponse.entity.lastrevid;
	} );

	[
		( statementId ) => newGetPropertyStatementRequestBuilder( testPropertyId, statementId ),
		newGetStatementRequestBuilder
	].forEach( ( newRequestBuilder ) => {
		describe( newRequestBuilder().getRouteDescription(), () => {
			it( '200 OK response is valid', async () => {
				const response = await newRequestBuilder( testStatementId ).makeRequest();

				expect( response ).to.have.status( 200 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '304 Not Modified response is valid', async () => {
				const response = await newRequestBuilder( testStatementId )
					.withHeader( 'If-None-Match', `"${lastRevId}"` )
					.makeRequest();

				expect( response ).to.have.status( 304 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '400 Bad Request for invalid Statement ID', async () => {
				const invalidStatementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
				const response = await newRequestBuilder( invalidStatementId ).makeRequest();

				expect( response ).to.have.status( 400 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '404 Not Found response is valid for a non-existing statement', async () => {
				const nonexistentStatement = `${testPropertyId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;

				const response = await newRequestBuilder( nonexistentStatement ).makeRequest();

				expect( response ).to.have.status( 404 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '404 Not Found response is valid for a non-existing property', async () => {
				const nonexistentProperty = 'P99999';
				const response = await newRequestBuilder(
					`${nonexistentProperty}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE` )
					.makeRequest();

				expect( response ).to.have.status( 404 );
				expect( response ).to.satisfyApiSpec;
			} );
		} );
	} );
} );
