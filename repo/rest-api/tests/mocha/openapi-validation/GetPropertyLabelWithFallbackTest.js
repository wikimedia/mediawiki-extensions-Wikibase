'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
const { newGetPropertyLabelWithFallbackRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetPropertyLabelWithFallbackRequestBuilder().getRouteDescription(), () => {

	let propertyId;
	let lastRevisionId;
	const languageCode = 'en';

	before( async () => {
		const createPropertyResponse = await createEntity( 'property', {
			labels: [ { language: languageCode, value: 'an-English-label-' + utils.uniq() } ],
			datatype: 'string'
		} );

		propertyId = createPropertyResponse.entity.id;
		lastRevisionId = createPropertyResponse.entity.lastrevid;
	} );

	it( '200 OK response is valid', async () => {
		const response = await newGetPropertyLabelWithFallbackRequestBuilder( propertyId, languageCode )
			.makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetPropertyLabelWithFallbackRequestBuilder( propertyId, languageCode )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '307 Temporary Redirect response is valid for a label with language fallback', async () => {
		const languageCodeWithFallback = 'en-ca';
		const response = await newGetPropertyLabelWithFallbackRequestBuilder(
			propertyId,
			languageCodeWithFallback
		).makeRequest();

		expect( response ).to.have.status( 307 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid property ID', async () => {
		const response = await newGetPropertyLabelWithFallbackRequestBuilder( 'X123', languageCode )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it(
		'404 Not Found response is valid if there is no label in the requested or any fallback language',
		async () => {
			const propertyWithoutFallback = await createEntity( 'property', {
				labels: { de: { language: 'de', value: `de-label-${utils.uniq()}` } },
				datatype: 'string'
			} );

			const response = await newGetPropertyLabelWithFallbackRequestBuilder(
				propertyWithoutFallback.entity.id,
				'ko'
			).makeRequest();

			expect( response ).to.have.status( 404 );
			expect( response ).to.satisfyApiSpec;
		}
	);

} );
