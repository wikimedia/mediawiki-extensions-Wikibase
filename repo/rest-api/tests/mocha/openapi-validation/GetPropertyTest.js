'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newGetPropertyRequestBuilder, newCreatePropertyRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

async function createPropertyWithAllFields() {
	const statementPropertyId = ( await entityHelper.createUniqueStringProperty() ).body.id;

	return ( await newCreatePropertyRequestBuilder( {
		data_type: 'string',
		labels: { en: `non-empty-string-property-${utils.uniq()}` },
		descriptions: { en: 'non-empty-string-property-description' },
		aliases: { en: [ 'non-empty-string-property-alias' ] },
		statements: { [ statementPropertyId ]: [
			{ // with value, without qualifiers or references
				property: { id: statementPropertyId },
				value: { type: 'value', content: 'im a statement value' },
				rank: 'normal'
			},
			{ // no value, with qualifier and reference
				property: { id: statementPropertyId },
				value: { type: 'novalue' },
				rank: 'normal',
				qualifiers: [
					{
						property: { id: statementPropertyId },
						value: { type: 'value', content: 'im a qualifier value' }
					}
				],
				references: [ {
					parts: [ {
						property: { id: statementPropertyId },
						value: { type: 'value', content: 'im a reference value' }
					} ]
				} ]
			}
		] }
	} ).makeRequest() ).body.id;
}

describe( newGetPropertyRequestBuilder().getRouteDescription(), () => {

	it( '200 OK response is valid for a non-empty property', async () => {
		const id = await createPropertyWithAllFields();

		const response = await newGetPropertyRequestBuilder( id ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 Bad Request response is valid for an invalid property ID', async () => {
		const response = await newGetPropertyRequestBuilder( 'X123' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 Not Found response is valid for a non-existing property', async () => {
		const response = await newGetPropertyRequestBuilder( 'P99999' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

} );
