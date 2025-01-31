'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const {
	createRedirectForItem,
	createLocalSitelink,
	getLatestEditMetadata
} = require( '../helpers/entityHelper' );
const { newGetSitelinksRequestBuilder, newCreateItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetSitelinksRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let lastRevisionId;

	const linkedArticle = utils.title( 'Article-linked-to-test-item' );

	before( async () => {
		const item = ( await newCreateItemRequestBuilder( {} ).makeRequest() );
		testItemId = item.body.id;
		await createLocalSitelink( testItemId, linkedArticle );

		const testItemCreationMetadata = await getLatestEditMetadata( testItemId );
		lastRevisionId = testItemCreationMetadata.revid;
	} );

	it( '200 OK response is valid for an Item with sitelinks', async () => {
		const response = await newGetSitelinksRequestBuilder( testItemId ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetSitelinksRequestBuilder( testItemId )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await createRedirectForItem( testItemId );

		const response = await newGetSitelinksRequestBuilder( redirectSourceId ).makeRequest();

		expect( response ).to.have.status( 308 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetSitelinksRequestBuilder( 'X123' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetSitelinksRequestBuilder( 'Q99999' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

} );
