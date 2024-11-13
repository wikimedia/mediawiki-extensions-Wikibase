'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	newGetPropertyLabelWithFallbackRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { getLatestEditMetadata } = require( '../helpers/entityHelper' );

describe( newGetPropertyLabelWithFallbackRequestBuilder().getRouteDescription(), () => {

	let propertyId;
	let lastRevisionId;
	const languageCode = 'en';

	before( async () => {
		const createPropertyResponse = await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			labels: { [ languageCode ]: 'an-English-label-' + utils.uniq() }
		} ).makeRequest();

		propertyId = createPropertyResponse.body.id;
		lastRevisionId = ( await getLatestEditMetadata( propertyId ) ).revid;
	} );

	it( '200 OK response is valid', async () => {
		const response = await newGetPropertyLabelWithFallbackRequestBuilder( propertyId, languageCode )
			.makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetPropertyLabelWithFallbackRequestBuilder( propertyId, languageCode )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '307 Temporary Redirect response is valid for a label with language fallback', async () => {
		const languageCodeWithFallback = 'en-ca';
		const response = await newGetPropertyLabelWithFallbackRequestBuilder(
			propertyId,
			languageCodeWithFallback
		).makeRequest();

		expect( response ).to.have.status( 307 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 Bad Request response is valid for an invalid property ID', async () => {
		const response = await newGetPropertyLabelWithFallbackRequestBuilder( 'X123', languageCode )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it(
		'404 Not Found response is valid if there is no label in the requested or any fallback language',
		async () => {
			const propertyWithoutFallback = await newCreatePropertyRequestBuilder( {
				data_type: 'string',
				labels: { de: `de-label-${utils.uniq()}` }
			} ).makeRequest();

			const response = await newGetPropertyLabelWithFallbackRequestBuilder(
				propertyWithoutFallback.body.id,
				'ko'
			).makeRequest();

			expect( response ).to.have.status( 404 );
			expect( response ).to.satisfyApiSchema;
		}
	);

} );
