'use strict';

const { utils } = require( 'api-testing' );
const chai = require( 'chai' );
const { createEntity, createRedirectForItem } = require( '../helpers/entityHelper' );
const { newGetItemLabelsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const expect = chai.expect;

const germanLabel = 'a-German-label-' + utils.uniq();
const englishLabel = 'an-English-label-' + utils.uniq();

describe( 'validate GET /entities/items/{id}/labels responses against OpenAPI spec', () => {

	let testItemId;
	let lastRevisionId;

	before( async () => {
		const createItemResponse = await createEntity( 'item', {
			labels: {
				de: { language: 'de', value: germanLabel },
				en: { language: 'en', value: englishLabel }
			}
		} );

		testItemId = createItemResponse.entity.id;
		lastRevisionId = createItemResponse.entity.lastrevid;
	} );

	it( '200 OK response is valid for an Item with two labels', async () => {
		const response = await newGetItemLabelsRequestBuilder( testItemId ).makeRequest();

		expect( response.status ).to.equal( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '200 OK response is valid for an Item without labels', async () => {
		const createItemResponse = await createEntity( 'item', {
			descriptions: { en: { language: 'en', value: 'some description' } }
		} );

		const response = await newGetItemLabelsRequestBuilder( createItemResponse.entity.id ).makeRequest();

		expect( response.status ).to.equal( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetItemLabelsRequestBuilder( testItemId )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response.status ).to.equal( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await createRedirectForItem( testItemId );

		const response = await newGetItemLabelsRequestBuilder( redirectSourceId ).makeRequest();

		expect( response.status ).to.equal( 308 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetItemLabelsRequestBuilder( 'X123' ).makeRequest();

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetItemLabelsRequestBuilder( 'Q99999' ).makeRequest();

		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
