'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createEntity,
	createLocalSitelink,
	getLocalSiteId,
	createRedirectForItem, createWikiPage
} = require( '../helpers/entityHelper' );
const { newSetSitelinkRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
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
		const createItemResponse = await createEntity( 'item', {} );
		testItemId = createItemResponse.entity.id;

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
		expect( response ).to.satisfyApiSpec;
	} );

	it( '201 - sitelink created', async () => {
		const articleTitle = makeSitelinkTitle();
		await createWikiPage( articleTitle, 'wiki page test' );
		const createItemResponse = await createEntity( 'item', {} );

		const response = await newSetSitelinkRequestBuilder(
			createItemResponse.entity.id,
			siteId,
			{ title: articleTitle }
		).makeRequest();

		expect( response ).to.have.status( 201 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 - invalid item id', async () => {
		const response = await newSetSitelinkRequestBuilder( 'X123', siteId, sitelink )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 - item does not exist', async () => {
		const title = makeSitelinkTitle();
		await createWikiPage( title );
		const response = await newSetSitelinkRequestBuilder( 'Q9999999', siteId, { title } )
			.makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '409 - item redirected', async () => {
		const redirectSource = await createRedirectForItem( testItemId );
		const response = await newSetSitelinkRequestBuilder( redirectSource, siteId, sitelink )
			.makeRequest();

		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newSetSitelinkRequestBuilder( testItemId, siteId, sitelink )
			.withHeader( 'If-Unmodified-Since', yesterday )
			.makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSpec;
	} );
} );
