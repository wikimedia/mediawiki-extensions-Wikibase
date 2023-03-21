'use strict';

const { utils } = require( 'api-testing' );
const chai = require( 'chai' );
const { createEntity, createRedirectForItem } = require( '../helpers/entityHelper' );
const { newGetItemDescriptionRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const expect = chai.expect;

describe( newGetItemDescriptionRequestBuilder().getRouteDescription(), () => {

	let itemId;
	let lastRevisionId;
	const languageCode = 'en';

	before( async () => {
		const createItemResponse = await createEntity( 'item', {
			descriptions: [ { language: languageCode, value: 'an-English-description-' + utils.uniq() } ]
		} );

		itemId = createItemResponse.entity.id;
		lastRevisionId = createItemResponse.entity.lastrevid;
	} );

	it( '200 OK response is valid', async () => {
		const response = await newGetItemDescriptionRequestBuilder( itemId, languageCode ).makeRequest();
		expect( response.status ).to.equal( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetItemDescriptionRequestBuilder( itemId, languageCode )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();
		expect( response.status ).to.equal( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await createRedirectForItem( itemId );
		const response = await newGetItemDescriptionRequestBuilder( redirectSourceId, languageCode ).makeRequest();
		expect( response.status ).to.equal( 308 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetItemDescriptionRequestBuilder( 'X123', languageCode ).makeRequest();
		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetItemDescriptionRequestBuilder( 'Q99999', languageCode ).makeRequest();
		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid if there is no description in the requested language', async () => {
		const response = await newGetItemDescriptionRequestBuilder( itemId, 'de' ).makeRequest();
		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
