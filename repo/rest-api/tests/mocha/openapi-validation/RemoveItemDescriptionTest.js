'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity, createRedirectForItem } = require( '../helpers/entityHelper' );
const {
	newRemoveItemDescriptionRequestBuilder,
	newSetItemDescriptionRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

function makeUnique( text ) {
	return `${text}-${utils.uniq()}`;
}

describe( newRemoveItemDescriptionRequestBuilder().getRouteDescription(), () => {

	let existingItemId;

	before( async () => {
		const createItemResponse = await createEntity( 'item', {
			descriptions: [ { language: 'en', value: makeUnique( 'unique description' ) } ]
		} );

		existingItemId = createItemResponse.entity.id;
	} );

	it( '200 - description removed', async () => {
		const response = await newRemoveItemDescriptionRequestBuilder( existingItemId, 'en' ).makeRequest();
		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;

		// replace removed statement
		await newSetItemDescriptionRequestBuilder( existingItemId, 'en', makeUnique( 'updated description' ) )
			.makeRequest();
	} );

	it( '400 - invalid language code', async () => {
		const response = await newRemoveItemDescriptionRequestBuilder( existingItemId, 'xyz' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 - item does not exist', async () => {
		const response = await newRemoveItemDescriptionRequestBuilder( 'Q9999999', 'en' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '409 - item redirected', async () => {
		const redirectSource = await createRedirectForItem( existingItemId );
		const response = await newRemoveItemDescriptionRequestBuilder( redirectSource, 'en' ).makeRequest();

		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newRemoveItemDescriptionRequestBuilder( existingItemId, 'en' )
			.withHeader( 'If-Unmodified-Since', yesterday ).makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '415 - unsupported media type', async () => {
		const response = await newRemoveItemDescriptionRequestBuilder( existingItemId, 'en' )
			.withHeader( 'Content-Type', 'text/plain' ).makeRequest();

		expect( response ).to.have.status( 415 );
		expect( response ).to.satisfyApiSpec;
	} );
} );
