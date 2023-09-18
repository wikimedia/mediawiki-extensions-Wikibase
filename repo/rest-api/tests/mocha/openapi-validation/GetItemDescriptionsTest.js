'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity, createRedirectForItem } = require( '../helpers/entityHelper' );
const { newGetItemDescriptionsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetItemDescriptionsRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let lastRevisionId;

	before( async () => {
		const createItemResponse = await createEntity( 'item', {
			descriptions: {
				de: { language: 'de', value: 'a-German-description-' + utils.uniq() },
				en: { language: 'en', value: 'an-English-description-' + utils.uniq() }
			}
		} );

		testItemId = createItemResponse.entity.id;
		lastRevisionId = createItemResponse.entity.lastrevid;
	} );

	it( '200 OK response is valid for an Item with two descriptions', async () => {
		const response = await newGetItemDescriptionsRequestBuilder( testItemId ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '200 OK response is valid for an Item without descriptions', async () => {
		const createItemResponse = await createEntity( 'item', {} );

		const response = await newGetItemDescriptionsRequestBuilder( createItemResponse.entity.id ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetItemDescriptionsRequestBuilder( testItemId )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await createRedirectForItem( testItemId );

		const response = await newGetItemDescriptionsRequestBuilder( redirectSourceId ).makeRequest();

		expect( response ).to.have.status( 308 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetItemDescriptionsRequestBuilder( 'X123' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetItemDescriptionsRequestBuilder( 'Q99999' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
