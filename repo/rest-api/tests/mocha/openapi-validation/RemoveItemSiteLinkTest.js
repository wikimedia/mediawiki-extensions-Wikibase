'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createEntity,
	createRedirectForItem,
	createLocalSiteLink,
	getLocalSiteId
} = require( '../helpers/entityHelper' );
const { newRemoveItemSiteLinkRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newRemoveItemSiteLinkRequestBuilder().getRouteDescription(), () => {

	let itemId;
	let localSiteId;
	const linkedArticle = utils.title( 'article-linked-to-test-item' );

	before( async () => {
		itemId = ( await createEntity( 'item', {} ) ).entity.id;
		await createLocalSiteLink( itemId, linkedArticle );
		localSiteId = await getLocalSiteId();
	} );

	describe( '200 OK', () => {
		after( async () => {
			await createLocalSiteLink( itemId, linkedArticle ); // reset removed sitelink
		} );

		it( 'sitelink removed', async () => {
			const response = await newRemoveItemSiteLinkRequestBuilder( itemId, localSiteId ).makeRequest();
			expect( response ).to.have.status( 200 );
			expect( response ).to.satisfyApiSpec;
		} );
	} );

	it( '400 - invalid site ID', async () => {
		const response = await newRemoveItemSiteLinkRequestBuilder( itemId, 'not-a-valid-site' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 - item does not exist', async () => {
		const response = await newRemoveItemSiteLinkRequestBuilder( 'Q9999999', localSiteId ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '409 - item redirected', async () => {
		const redirectSource = await createRedirectForItem( itemId );
		const response = await newRemoveItemSiteLinkRequestBuilder( redirectSource, localSiteId ).makeRequest();

		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newRemoveItemSiteLinkRequestBuilder( itemId, localSiteId )
			.withHeader( 'If-Unmodified-Since', yesterday ).makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '415 - unsupported media type', async () => {
		const response = await newRemoveItemSiteLinkRequestBuilder( itemId, localSiteId )
			.withHeader( 'Content-Type', 'text/plain' ).makeRequest();

		expect( response ).to.have.status( 415 );
		expect( response ).to.satisfyApiSpec;
	} );
} );
