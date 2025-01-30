'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { createRedirectForItem, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { newGetItemLabelRequestBuilder, newCreateItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetItemLabelRequestBuilder().getRouteDescription(), () => {

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
		const response = await newGetItemLabelRequestBuilder( itemId, languageCode ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetItemLabelRequestBuilder( itemId, languageCode )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await createRedirectForItem( itemId );

		const response = await newGetItemLabelRequestBuilder( redirectSourceId, languageCode ).makeRequest();

		expect( response ).to.have.status( 308 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetItemLabelRequestBuilder( 'X123', languageCode ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetItemLabelRequestBuilder( 'Q99999', languageCode ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

} );
