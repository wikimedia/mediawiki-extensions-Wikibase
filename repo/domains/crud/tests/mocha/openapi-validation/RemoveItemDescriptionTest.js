'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { createRedirectForItem } = require( '../helpers/entityHelper' );
const {
	newRemoveItemDescriptionRequestBuilder,
	newSetItemDescriptionRequestBuilder,
	newCreateItemRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

function makeUnique( text ) {
	return `${text}-${utils.uniq()}`;
}

describe( newRemoveItemDescriptionRequestBuilder().getRouteDescription(), () => {

	let existingItemId;

	before( async () => {
		const createItemResponse = await newCreateItemRequestBuilder( {
			descriptions: { en: makeUnique( 'unique description' ) }
		} ).makeRequest();

		existingItemId = createItemResponse.body.id;
	} );

	it( '200 - description removed', async () => {
		const response = await newRemoveItemDescriptionRequestBuilder( existingItemId, 'en' ).makeRequest();
		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;

		// replace removed statement
		await newSetItemDescriptionRequestBuilder( existingItemId, 'en', makeUnique( 'updated description' ) )
			.makeRequest();
	} );

	it( '400 - invalid language code', async () => {
		const response = await newRemoveItemDescriptionRequestBuilder( existingItemId, 'xyz' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 - item does not exist', async () => {
		const response = await newRemoveItemDescriptionRequestBuilder( 'Q9999999', 'en' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '409 - item redirected', async () => {
		const redirectSource = await createRedirectForItem( existingItemId );
		const response = await newRemoveItemDescriptionRequestBuilder( redirectSource, 'en' ).makeRequest();

		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newRemoveItemDescriptionRequestBuilder( existingItemId, 'en' )
			.withHeader( 'If-Unmodified-Since', yesterday ).makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSchema;
	} );
} );
