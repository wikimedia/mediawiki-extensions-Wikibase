'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const {
	createRedirectForItem,
	createLocalSitelink,
	getLatestEditMetadata,
	getLocalSiteId
} = require( '../helpers/entityHelper' );
const { newGetSitelinkRequestBuilder, newCreateItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetSitelinkRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let siteId;
	let lastRevisionId;

	const linkedArticle = utils.title( 'Article-linked-to-test-item' );

	before( async () => {
		testItemId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		await createLocalSitelink( testItemId, linkedArticle );
		siteId = await getLocalSiteId();
		lastRevisionId = ( await getLatestEditMetadata( testItemId ) ).revid;
	} );

	it( '200 OK response is valid for an Item with the requested sitelink', async () => {
		const response = await newGetSitelinkRequestBuilder( testItemId, siteId ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetSitelinkRequestBuilder( testItemId, siteId )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await createRedirectForItem( testItemId );

		const response = await newGetSitelinkRequestBuilder( redirectSourceId, siteId ).makeRequest();

		expect( response ).to.have.status( 308 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetSitelinkRequestBuilder( 'X123', siteId ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetSitelinkRequestBuilder( 'Q99999', siteId ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

} );
