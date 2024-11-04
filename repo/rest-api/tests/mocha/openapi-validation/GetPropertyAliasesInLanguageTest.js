'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	newGetPropertyAliasesInLanguageRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { getLatestEditMetadata } = require( '../helpers/entityHelper' );

describe( newGetPropertyAliasesInLanguageRequestBuilder().getRouteDescription(), () => {

	let propertyId;
	let lastRevisionId;
	const languageCode = 'en';

	before( async () => {
		const createPropertyResponse = await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			aliases: { en: [ 'an-English-alias-' + utils.uniq(), 'another-English-alias-' + utils.uniq() ] }
		} ).makeRequest();
		propertyId = createPropertyResponse.body.id;
		lastRevisionId = ( await getLatestEditMetadata( propertyId ) ).revid;
	} );

	it( '200 OK response is valid', async () => {
		const response = await newGetPropertyAliasesInLanguageRequestBuilder( propertyId, languageCode ).makeRequest();
		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetPropertyAliasesInLanguageRequestBuilder( propertyId, languageCode )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();
		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid property ID', async () => {
		const response = await newGetPropertyAliasesInLanguageRequestBuilder( 'X123', languageCode )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing property', async () => {
		const response = await newGetPropertyAliasesInLanguageRequestBuilder( 'P99999', languageCode )
			.makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
