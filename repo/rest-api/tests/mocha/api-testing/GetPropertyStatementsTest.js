'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createUniqueStringProperty,
	newLegacyStatementWithRandomStringValue, createEntity
} = require( '../helpers/entityHelper' );
const { newGetPropertyStatementsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( 'GET /entities/properties/{id}/statements', () => {

	let subjectPropertyId;

	let testPropertyId1;
	let testPropertyId2;

	let testStatements;

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
	} );

	it( 'can GET statements of a property', async () => {
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

} );
