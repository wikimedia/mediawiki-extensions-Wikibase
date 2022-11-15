'use strict';

const { createUniqueStringProperty, createEntity, createRedirectForItem } = require( '../helpers/entityHelper' );
const { newAddItemStatementRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const expect = require( 'chai' ).expect;

describe( 'validate POST /entities/items/{id}/statements', () => {

	let validStatementSerialization;
	let itemId;

	before( async () => {
		itemId = ( await createEntity( 'item', {} ) ).entity.id;
		const propertyId = ( await createUniqueStringProperty() ).entity.id;
		validStatementSerialization = {
			value: { type: 'novalue' },
			property: { id: propertyId }
		};
	} );

	it( '201 Created is valid', async () => {
		const response = await newAddItemStatementRequestBuilder(
			itemId,
			validStatementSerialization
		).makeRequest();

		expect( response.status ).to.equal( 201 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found is valid for non-existent Item', async () => {
		const response = await newAddItemStatementRequestBuilder( 'Q9999999', validStatementSerialization )
			.makeRequest();

		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request is valid for invalid statement', async () => {
		const response = await newAddItemStatementRequestBuilder(
			itemId,
			{ invalid: 'statement' }
		).makeRequest();

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request is valid for invalid statement param type', async () => {
		const response = await newAddItemStatementRequestBuilder(
			itemId,
			'invalid statement param type'
		).makeRequest();

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '409 Conflict is valid for redirected Item', async () => {
		const redirectSource = await createRedirectForItem( itemId );
		const response = await newAddItemStatementRequestBuilder(
			redirectSource,
			validStatementSerialization
		).makeRequest();

		expect( response.status ).to.equal( 409 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newAddItemStatementRequestBuilder( itemId, validStatementSerialization )
			.withHeader( 'If-Unmodified-Since', yesterday )
			.makeRequest();

		expect( response.status ).to.equal( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
