'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createRedirectForItem, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const {
	newGetItemDescriptionsRequestBuilder,
	newCreateItemRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( newGetItemDescriptionsRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let lastRevisionId;

	before( async () => {
		const createItemResponse = await newCreateItemRequestBuilder( {
			descriptions: {
				de: 'a-German-description-' + utils.uniq(),
				en: 'an-English-description-' + utils.uniq()
			}
		} ).makeRequest();
		testItemId = createItemResponse.body.id;
		lastRevisionId = ( await getLatestEditMetadata( testItemId ) ).revid;
	} );

	it( '200 OK response is valid for an Item with two descriptions', async () => {
		const response = await newGetItemDescriptionsRequestBuilder( testItemId ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetItemDescriptionsRequestBuilder( testItemId )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await createRedirectForItem( testItemId );

		const response = await newGetItemDescriptionsRequestBuilder( redirectSourceId ).makeRequest();

		expect( response ).to.have.status( 308 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetItemDescriptionsRequestBuilder( 'X123' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetItemDescriptionsRequestBuilder( 'Q99999' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

} );
