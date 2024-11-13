'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createRedirectForItem, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const {
	newGetItemLabelWithFallbackRequestBuilder,
	newCreateItemRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( newGetItemLabelWithFallbackRequestBuilder().getRouteDescription(), () => {

	let itemId;
	let lastRevisionId;
	const languageCode = 'en';

	before( async () => {
		const createItemResponse = await newCreateItemRequestBuilder( {
			labels: { [ languageCode ]: 'an-English-label-' + utils.uniq() }
		} ).makeRequest();
		itemId = createItemResponse.body.id;
		lastRevisionId = ( await getLatestEditMetadata( itemId ) ).revid;
	} );

	it( '200 OK response is valid', async () => {
		const response = await newGetItemLabelWithFallbackRequestBuilder( itemId, languageCode ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetItemLabelWithFallbackRequestBuilder( itemId, languageCode )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '307 Temporary Redirect response is valid for a label with language fallback', async () => {
		const languageCodeWithFallback = 'en-ca';
		const response = await newGetItemLabelWithFallbackRequestBuilder( itemId, languageCodeWithFallback ).makeRequest();

		expect( response ).to.have.status( 307 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await createRedirectForItem( itemId );

		const response = await newGetItemLabelWithFallbackRequestBuilder( redirectSourceId, languageCode ).makeRequest();

		expect( response ).to.have.status( 308 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetItemLabelWithFallbackRequestBuilder( 'X123', languageCode ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetItemLabelWithFallbackRequestBuilder( 'Q99999', languageCode ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
