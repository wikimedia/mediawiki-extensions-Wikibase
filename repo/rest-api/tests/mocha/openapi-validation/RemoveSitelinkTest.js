'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createRedirectForItem,
	createLocalSitelink,
	getLocalSiteId
} = require( '../helpers/entityHelper' );
const {
	newRemoveSitelinkRequestBuilder,
	newCreateItemRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( newRemoveSitelinkRequestBuilder().getRouteDescription(), () => {

	let itemId;
	let localSiteId;
	const linkedArticle = utils.title( 'article-linked-to-test-item' );

	before( async () => {
		itemId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		await createLocalSitelink( itemId, linkedArticle );
		localSiteId = await getLocalSiteId();
	} );

	describe( '200 OK', () => {
		after( async () => {
			await createLocalSitelink( itemId, linkedArticle ); // reset removed sitelink
		} );

		it( 'sitelink removed', async () => {
			const response = await newRemoveSitelinkRequestBuilder( itemId, localSiteId ).makeRequest();
			expect( response ).to.have.status( 200 );
			expect( response ).to.satisfyApiSchema;
		} );
	} );

	it( '400 - invalid site ID', async () => {
		const response = await newRemoveSitelinkRequestBuilder( itemId, 'not-a-valid-site' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 - item does not exist', async () => {
		const response = await newRemoveSitelinkRequestBuilder( 'Q9999999', localSiteId ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '409 - item redirected', async () => {
		const redirectSource = await createRedirectForItem( itemId );
		const response = await newRemoveSitelinkRequestBuilder( redirectSource, localSiteId ).makeRequest();

		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newRemoveSitelinkRequestBuilder( itemId, localSiteId )
			.withHeader( 'If-Unmodified-Since', yesterday ).makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSchema;
	} );
} );
