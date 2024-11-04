'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	newGetPropertyAliasesRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { getLatestEditMetadata } = require( '../helpers/entityHelper' );

describe( newGetPropertyAliasesRequestBuilder().getRouteDescription(), () => {

	let testPropertyId;
	let lastRevisionId;

	before( async () => {
		const createPropertyResponse = await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			aliases: {
				de: [ 'a-German-alias-' + utils.uniq(), 'another-German-alias-' + utils.uniq() ],
				en: [ 'an-English-alias-' + utils.uniq(), 'another-English-alias-' + utils.uniq() ]
			}
		} ).makeRequest();

		testPropertyId = createPropertyResponse.body.id;
		lastRevisionId = ( await getLatestEditMetadata( testPropertyId ) ).revid;
	} );

	it( '200 OK response is valid for a Property with two aliases', async () => {
		const response = await newGetPropertyAliasesRequestBuilder( testPropertyId ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetPropertyAliasesRequestBuilder( testPropertyId )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid property ID', async () => {
		const response = await newGetPropertyAliasesRequestBuilder( 'X123' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing property', async () => {
		const response = await newGetPropertyAliasesRequestBuilder( 'P99999' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
