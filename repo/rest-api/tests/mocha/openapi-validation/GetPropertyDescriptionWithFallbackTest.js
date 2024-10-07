'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
const { newGetPropertyDescriptionWithFallbackRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetPropertyDescriptionWithFallbackRequestBuilder().getRouteDescription(), () => {

	let propertyId;
	let lastRevisionId;
	const languageCode = 'en';

	before( async () => {
		const createPropertyResponse = await createEntity( 'property', {
			descriptions: [ { language: languageCode, value: 'an-English-description-' + utils.uniq() } ],
			datatype: 'string'
		} );

		propertyId = createPropertyResponse.entity.id;
		lastRevisionId = createPropertyResponse.entity.lastrevid;
	} );

	it( '200 OK response is valid', async () => {
		const response = await newGetPropertyDescriptionWithFallbackRequestBuilder( propertyId, languageCode ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetPropertyDescriptionWithFallbackRequestBuilder( propertyId, languageCode )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid property ID', async () => {
		const response = await newGetPropertyDescriptionWithFallbackRequestBuilder( 'X123', languageCode ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing property', async () => {
		const response = await newGetPropertyDescriptionWithFallbackRequestBuilder( 'P99999', languageCode ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid if there is no description in the requested language', async () => {
		const response = await newGetPropertyDescriptionWithFallbackRequestBuilder( propertyId, 'ko' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
