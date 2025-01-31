'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const {
	createLocalSitelink,
	getLocalSiteId,
	createRedirectForItem,
	createWikiPage
} = require( '../helpers/entityHelper' );
const { newSetSitelinkRequestBuilder, newCreateItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { getAllowedBadges } = require( '../helpers/getAllowedBadges' );

function makeSitelinkTitle() {
	return utils.title( 'test-title-' );
}

describe( newSetSitelinkRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let siteId;
	const linkedArticle = makeSitelinkTitle();
	const sitelink = { title: linkedArticle };

	before( async () => {
		const createItemResponse = await newCreateItemRequestBuilder( {} ).makeRequest();
		testItemId = createItemResponse.body.id;

		await createLocalSitelink( testItemId, linkedArticle );
		siteId = await getLocalSiteId();
	} );

	it( '200 - sitelink replaced', async () => {
		const response = await newSetSitelinkRequestBuilder(
			testItemId,
			siteId,
			{ title: linkedArticle, badges: [ ( await getAllowedBadges() )[ 0 ] ] }
		).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '201 - sitelink created', async () => {
		const articleTitle = makeSitelinkTitle();
		await createWikiPage( articleTitle, 'wiki page test' );
		const createItemResponse = await newCreateItemRequestBuilder( {} ).makeRequest();

		const response = await newSetSitelinkRequestBuilder(
			createItemResponse.body.id,
			siteId,
			{ title: articleTitle }
		).makeRequest();

		expect( response ).to.have.status( 201 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 - invalid item id', async () => {
		const response = await newSetSitelinkRequestBuilder( 'X123', siteId, sitelink )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 - item does not exist', async () => {
		const title = makeSitelinkTitle();
		await createWikiPage( title );
		const response = await newSetSitelinkRequestBuilder( 'Q9999999', siteId, { title } )
			.makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '409 - item redirected', async () => {
		const title = makeSitelinkTitle();
		await createWikiPage( title, 'wiki page test' );

		const redirectSource = await createRedirectForItem( testItemId );
		const response = await newSetSitelinkRequestBuilder( redirectSource, siteId, { title } )
			.makeRequest();

		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newSetSitelinkRequestBuilder( testItemId, siteId, sitelink )
			.withHeader( 'If-Unmodified-Since', yesterday )
			.makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '422 - sitelink conflict', async () => {
		const articleTitle = makeSitelinkTitle();
		await createWikiPage( articleTitle, 'wiki page test' );
		const createItemResponse = await newCreateItemRequestBuilder( {} ).makeRequest();

		await newSetSitelinkRequestBuilder(
			createItemResponse.body.id,
			siteId,
			{ title: articleTitle }
		).makeRequest();

		const response = await newSetSitelinkRequestBuilder(
			testItemId,
			siteId,
			{ title: articleTitle }
		).makeRequest();

		expect( response ).to.have.status( 422 );
		expect( response ).to.satisfyApiSchema;
	} );
} );
