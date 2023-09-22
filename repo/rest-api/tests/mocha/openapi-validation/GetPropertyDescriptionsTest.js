'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity, createUniqueStringProperty } = require( '../helpers/entityHelper' );
const { newGetPropertyDescriptionsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetPropertyDescriptionsRequestBuilder().getRouteDescription(), () => {

	let testPropertyId;
	let lastRevisionId;

	before( async () => {
		const createPropertyResponse = await createEntity( 'property', {
			descriptions: {
				en: { language: 'en', value: `english-property-description-${utils.uniq()}` },
				de: { language: 'de', value: `german-property-description-${utils.uniq()}` }
			},
			datatype: 'string'
		} );

		testPropertyId = createPropertyResponse.entity.id;
		lastRevisionId = createPropertyResponse.entity.lastrevid;
	} );

	it( '200 OK response is valid for a Property with two descriptions', async () => {
		const response = await newGetPropertyDescriptionsRequestBuilder( testPropertyId ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '200 OK response is valid for a Property without descriptions', async () => {
		const createPropertyResponse = await createUniqueStringProperty();

		const response = await newGetPropertyDescriptionsRequestBuilder( createPropertyResponse.entity.id )
			.makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetPropertyDescriptionsRequestBuilder( testPropertyId )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid property ID', async () => {
		const response = await newGetPropertyDescriptionsRequestBuilder( 'X123' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing property', async () => {
		const response = await newGetPropertyDescriptionsRequestBuilder( 'P99999' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
