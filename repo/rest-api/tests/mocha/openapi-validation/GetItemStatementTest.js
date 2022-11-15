'use strict';

const chai = require( 'chai' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newGetItemStatementRequestBuilder,
	newGetStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const expect = chai.expect;

describe( 'validate GET statement responses', () => {

	let testItemId;
	let testStatementId;
	let lastRevId;

	before( async () => {
		const statementPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
		const createItemResponse = await entityHelper.createItemWithStatements( [
			entityHelper.newLegacyStatementWithRandomStringValue( statementPropertyId )
		] );
		testItemId = createItemResponse.entity.id;
		testStatementId = Object.values( createItemResponse.entity.claims )[ 0 ][ 0 ].id;
		lastRevId = createItemResponse.entity.lastrevid;
	} );

	[
		( statementId ) => newGetItemStatementRequestBuilder( testItemId, statementId ),
		newGetStatementRequestBuilder
	].forEach( ( newRequestBuilder ) => {
		describe( newRequestBuilder().getRouteDescription(), () => {
			it( '200 OK response is valid', async () => {
				const response = await newRequestBuilder( testStatementId )
					.makeRequest();

				expect( response.status ).to.equal( 200 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '304 Not Modified response is valid', async () => {
				const response = await newRequestBuilder( testStatementId )
					.withHeader( 'If-None-Match', `"${lastRevId}"` )
					.makeRequest();

				expect( response.status ).to.equal( 304 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '400 Bad Request for invalid Statement ID', async () => {
				const invalidStatementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
				const response = await newRequestBuilder( invalidStatementId )
					.makeRequest();

				expect( response.status ).to.equal( 400 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '404 Not Found response is valid for a non-existing statement', async () => {
				const nonexistentStatement = `${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`;

				const response = await newRequestBuilder( nonexistentStatement )
					.makeRequest();

				expect( response.status ).to.equal( 404 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '404 Not Found response is valid for a non-existing item', async () => {
				const nonexistentItem = 'Q99999';
				const response = await newRequestBuilder( `${nonexistentItem}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE` )
					.makeRequest();

				expect( response.status ).to.equal( 404 );
				expect( response ).to.satisfyApiSpec;
			} );
		} );
	} );
} );
