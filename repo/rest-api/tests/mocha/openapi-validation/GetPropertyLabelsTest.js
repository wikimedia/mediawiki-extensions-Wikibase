'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const { createUniqueStringProperty, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { newGetPropertyLabelsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetPropertyLabelsRequestBuilder().getRouteDescription(), () => {

	let testPropertyId;
	let lastRevisionId;

	before( async () => {
		const createPropertyResponse = await createUniqueStringProperty();
		testPropertyId = createPropertyResponse.body.id;
		lastRevisionId = ( await getLatestEditMetadata( testPropertyId ) ).revid;
	} );

	it( '200 OK response is valid for a Property with two labels', async () => {
		const response = await newGetPropertyLabelsRequestBuilder( testPropertyId ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetPropertyLabelsRequestBuilder( testPropertyId )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 Bad Request response is valid for an invalid property ID', async () => {
		const response = await newGetPropertyLabelsRequestBuilder( 'X123' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 Not Found response is valid for a non-existing property', async () => {
		const response = await newGetPropertyLabelsRequestBuilder( 'P99999' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

} );
