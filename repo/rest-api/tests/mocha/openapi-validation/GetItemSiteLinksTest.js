'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createEntity,
	createRedirectForItem,
	createLocalSiteLink,
	getLatestEditMetadata
} = require( '../helpers/entityHelper' );
const { newGetItemSiteLinksRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetItemSiteLinksRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let lastRevisionId;

	const linkedArticle = utils.title( 'Article-linked-to-test-item' );

	before( async () => {
		testItemId = ( await createEntity( 'item', {} ) ).entity.id;
		await createLocalSiteLink( testItemId, linkedArticle );

		const testItemCreationMetadata = await getLatestEditMetadata( testItemId );
		lastRevisionId = testItemCreationMetadata.revid;
	} );

	it( '200 OK response is valid for an Item with siteLinks', async () => {
		const response = await newGetItemSiteLinksRequestBuilder( testItemId ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '200 OK response is valid for an Item without siteLinks', async () => {
		const createItemResponse = await createEntity( 'item', {} );

		const response = await newGetItemSiteLinksRequestBuilder( createItemResponse.entity.id ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetItemSiteLinksRequestBuilder( testItemId )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await createRedirectForItem( testItemId );

		const response = await newGetItemSiteLinksRequestBuilder( redirectSourceId ).makeRequest();

		expect( response ).to.have.status( 308 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetItemSiteLinksRequestBuilder( 'X123' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetItemSiteLinksRequestBuilder( 'Q99999' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
