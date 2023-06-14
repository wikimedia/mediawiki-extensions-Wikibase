'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newGetPropertyRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

async function createPropertyWithAllFields() {
	const stringPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;

	return entityHelper.createEntity( 'property', {
		labels: { en: { language: 'en', value: `non-empty-string-property-${utils.uniq()}` } },
		descriptions: { en: { language: 'en', value: 'non-empty-string-property-description' } },
		aliases: { en: [ { language: 'en', value: 'non-empty-string-property-alias' } ] },
		datatype: 'string',
		claims: [
			{ // with value, without qualifiers or references
				mainsnak: {
					snaktype: 'value',
					property: stringPropertyId,
					datavalue: { value: 'im a statement value', type: 'string' }
				}, type: 'statement', rank: 'normal'
			},
			{ // no value, with qualifier and reference
				mainsnak: {
					snaktype: 'novalue',
					property: stringPropertyId
				},
				type: 'statement',
				rank: 'normal',
				qualifiers: [
					{
						snaktype: 'value',
						property: stringPropertyId,
						datavalue: { value: 'im a qualifier value', type: 'string' }
					}
				],
				references: [ {
					snaks: [ {
						snaktype: 'value',
						property: stringPropertyId,
						datavalue: { value: 'im a reference value', type: 'string' }
					} ]
				} ]
			}
		]
	} );
}

describe( 'validate GET /entities/properties/{id} responses against OpenAPI document', () => {

	let propertyId;

	before( async () => {
		const createPropertyResponse = await entityHelper.createEntity(
			'property',
			{ datatype: 'string' }
		);
		propertyId = createPropertyResponse.entity.id;
	} );

	it( '200 OK response is valid for an "empty" property', async () => {
		const response = await newGetPropertyRequestBuilder( propertyId ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '200 OK response is valid for a non-empty property', async () => {
		const { entity: { id } } = await createPropertyWithAllFields();
		const response = await newGetPropertyRequestBuilder( id ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid property ID', async () => {
		const response = await newGetPropertyRequestBuilder( 'X123' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid field', async () => {
		const response = await newGetPropertyRequestBuilder( 'Q123' )
			.withQueryParam( '_fields', 'unknown_field' )
			.assertInvalidRequest()
			.makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing property', async () => {
		const response = await newGetPropertyRequestBuilder( 'P99999' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
