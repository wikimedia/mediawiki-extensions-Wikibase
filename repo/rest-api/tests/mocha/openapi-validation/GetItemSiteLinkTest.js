'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createEntity,
	createRedirectForItem,
	createLocalSiteLink,
	getLatestEditMetadata,
	getLocalSiteId
} = require( '../helpers/entityHelper' );
const { newGetItemSiteLinkRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetItemSiteLinkRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let siteId;
	let lastRevisionId;

	const linkedArticle = utils.title( 'Article-linked-to-test-item' );

	before( async () => {
		testItemId = ( await createEntity( 'item', {} ) ).entity.id;
		await createLocalSiteLink( testItemId, linkedArticle );
		siteId = await getLocalSiteId();

		const testItemCreationMetadata = await getLatestEditMetadata( testItemId );
		lastRevisionId = testItemCreationMetadata.revid;
	} );

	it( '200 OK response is valid for an Item with the requested siteLink', async () => {
		const response = await newGetItemSiteLinkRequestBuilder( testItemId, siteId ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetItemSiteLinkRequestBuilder( testItemId, siteId )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await createRedirectForItem( testItemId );

		const response = await newGetItemSiteLinkRequestBuilder( redirectSourceId, siteId ).makeRequest();

		expect( response ).to.have.status( 308 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetItemSiteLinkRequestBuilder( 'X123', siteId ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetItemSiteLinkRequestBuilder( 'Q99999', siteId ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid if there is no sitelink for the requested site id', async () => {
		const itemId = ( await createEntity( 'item', {} ) ).entity.id;
		const response = await newGetItemSiteLinkRequestBuilder( itemId, siteId ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
