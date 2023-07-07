'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createUniqueStringProperty,
	newLegacyStatementWithRandomStringValue, createEntity, getLatestEditMetadata
} = require( '../helpers/entityHelper' );
const { newGetPropertyStatementsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );

describe( 'GET /entities/properties/{id}/statements', () => {

	let subjectPropertyId;

	let testPropertyId1;
	let testPropertyId2;

	let testStatements;

	let testModified;
	let testRevisionId;

	before( async () => {
		testPropertyId1 = ( await createUniqueStringProperty() ).entity.id;
		testPropertyId2 = ( await createUniqueStringProperty() ).entity.id;

		testStatements = [
			newLegacyStatementWithRandomStringValue( testPropertyId1 ),
			newLegacyStatementWithRandomStringValue( testPropertyId1 ),
			newLegacyStatementWithRandomStringValue( testPropertyId2 )
		];

		testStatements.forEach( ( statement ) => {
			statement.type = 'statement';
		} );

		const property = await createEntity( 'property', {
			claims: testStatements,
			datatype: 'string'
		} );

		subjectPropertyId = property.entity.id;

		const testPropertyCreationMetadata = await getLatestEditMetadata( subjectPropertyId );
		testModified = testPropertyCreationMetadata.timestamp;
		testRevisionId = testPropertyCreationMetadata.revid;
	} );

	it( 'can GET statements of a property with metadata', async () => {
		const response = await newGetPropertyStatementsRequestBuilder( subjectPropertyId )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.exists( response.body[ testPropertyId1 ] );
		assert.equal(
			response.body[ testPropertyId1 ][ 0 ].value.content,
			testStatements[ 0 ].mainsnak.datavalue.value
		);
		assert.equal(
			response.body[ testPropertyId1 ][ 1 ].value.content,
			testStatements[ 1 ].mainsnak.datavalue.value
		);
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, makeEtag( testRevisionId ) );

	} );

	it( 'can filter statements by property', async () => {
		const response = await newGetPropertyStatementsRequestBuilder( subjectPropertyId )
			.withQueryParam( 'property', testPropertyId1 )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual( Object.keys( response.body ), [ testPropertyId1 ] );
		assert.strictEqual( response.body[ testPropertyId1 ].length, 2 );
	} );

	it( 'can GET empty statements list', async () => {
		const createPropertyResponse = await createUniqueStringProperty();
		const response = await newGetPropertyStatementsRequestBuilder( createPropertyResponse.entity.id )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.empty( response.body );
	} );

	it( '404 error - property not found', async () => {
		const propertyId = 'P999999';
		const response = await newGetPropertyStatementsRequestBuilder( propertyId )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'property-not-found' );
		assert.include( response.body.message, propertyId );
	} );

	it( '400 error - bad request, invalid subject property ID', async () => {
		const propertyId = 'X123';
		const response = await newGetPropertyStatementsRequestBuilder( propertyId )
			.assertInvalidRequest()
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'invalid-property-id' );
		assert.include( response.body.message, propertyId );
	} );

	it( '400 error - bad request, invalid filter property ID', async () => {
		const filterPropertyId = 'X123';
		const response = await newGetPropertyStatementsRequestBuilder( subjectPropertyId )
			.withQueryParam( 'property', filterPropertyId )
			.assertInvalidRequest()
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'invalid-property-id' );
		assert.include( response.body.message, filterPropertyId );
	} );

} );
