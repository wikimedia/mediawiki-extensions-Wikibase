'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createRedirectForItem, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const {
	newGetItemDescriptionWithFallbackRequestBuilder,
	newCreateItemRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( newGetItemDescriptionWithFallbackRequestBuilder().getRouteDescription(), () => {

	let itemId;
	let lastRevisionId;
	const languageCode = 'de';

	before( async () => {
		const createItemResponse = await newCreateItemRequestBuilder( {
			descriptions: { [ languageCode ]: 'a-German-description-' + utils.uniq() }
		} ).makeRequest();
		itemId = createItemResponse.body.id;
		lastRevisionId = ( await getLatestEditMetadata( itemId ) ).revid;
	} );

	it( '200 OK response is valid', async () => {
		const response = await newGetItemDescriptionWithFallbackRequestBuilder( itemId, languageCode ).makeRequest();
		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetItemDescriptionWithFallbackRequestBuilder( itemId, languageCode )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();
		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '307 Temporary Redirect response is valid for a description with language fallback', async () => {
		const languageCodeWithFallback = 'bar';
		const response = await newGetItemDescriptionWithFallbackRequestBuilder(
			itemId,
			languageCodeWithFallback
		).makeRequest();

		expect( response ).to.have.status( 307 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await createRedirectForItem( itemId );
		const response = await newGetItemDescriptionWithFallbackRequestBuilder( redirectSourceId, languageCode ).makeRequest();
		expect( response ).to.have.status( 308 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetItemDescriptionWithFallbackRequestBuilder( 'X123', languageCode ).makeRequest();
		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid if there is no description in the requested language', async () => {
		const response = await newGetItemDescriptionWithFallbackRequestBuilder( itemId, 'ko' ).makeRequest();
		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
