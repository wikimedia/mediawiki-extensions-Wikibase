'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { getStringPropertyId } = require( '../helpers/entityHelper' );
const { newCreatePropertyRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newCreatePropertyRequestBuilder().getRouteDescription(), () => {
	it( '201 - full property', async () => {
		const statementProperty = await getStringPropertyId();
		const response = await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			labels: { en: utils.title( 'property label' ) },
			descriptions: { en: 'proeprty description' },
			aliases: { en: [ 'alias-1', 'alias-2' ] },
			statements: {
				[ statementProperty ]: [ {
					property: { id: statementProperty },
					value: { type: 'novalue' }
				} ]
			}
		} ).makeRequest();

		expect( response ).to.have.status( 201 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 - invalid field', async () => {
		const response = await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			labels: { en: 42 }
		} ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '422 - data policy violation', async () => {
		await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			labels: { en: 'property label duplicated' }
		} ).makeRequest();

		const response = await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			labels: { en: 'property label duplicated' }
		} ).makeRequest();

		expect( response ).to.have.status( 422 );
		expect( response ).to.satisfyApiSchema;
	} );
} );
