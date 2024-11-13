'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	newGetPropertyDescriptionsRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { getLatestEditMetadata } = require( '../helpers/entityHelper' );

describe( newGetPropertyDescriptionsRequestBuilder().getRouteDescription(), () => {

	let testPropertyId;
	let lastRevisionId;

	before( async () => {
		const createPropertyResponse = await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			descriptions: {
				en: `english-property-description-${utils.uniq()}`,
				de: `german-property-description-${utils.uniq()}`
			}
		} ).makeRequest();

		testPropertyId = createPropertyResponse.body.id;
		lastRevisionId = ( await getLatestEditMetadata( testPropertyId ) ).revid;
	} );

	it( '200 OK response is valid for a Property with two descriptions', async () => {
		const response = await newGetPropertyDescriptionsRequestBuilder( testPropertyId ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetPropertyDescriptionsRequestBuilder( testPropertyId )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 Bad Request response is valid for an invalid property ID', async () => {
		const response = await newGetPropertyDescriptionsRequestBuilder( 'X123' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 Not Found response is valid for a non-existing property', async () => {
		const response = await newGetPropertyDescriptionsRequestBuilder( 'P99999' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

} );
