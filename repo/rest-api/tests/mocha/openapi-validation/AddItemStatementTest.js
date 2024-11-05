'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const { createUniqueStringProperty, createRedirectForItem } = require( '../helpers/entityHelper' );
const {
	newAddItemStatementRequestBuilder,
	newCreateItemRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( newAddItemStatementRequestBuilder().getRouteDescription(), () => {

	let validStatementSerialization;
	let itemId;

	before( async () => {
		itemId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		const propertyId = ( await createUniqueStringProperty() ).body.id;
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

		expect( response ).to.have.status( 201 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 Not Found is valid for non-existent Item', async () => {
		const response = await newAddItemStatementRequestBuilder( 'Q9999999', validStatementSerialization )
			.makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 Bad Request is valid for invalid statement', async () => {
		const response = await newAddItemStatementRequestBuilder(
			itemId,
			{ invalid: 'statement' }
		).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '409 Conflict is valid for redirected Item', async () => {
		const redirectSource = await createRedirectForItem( itemId );
		const response = await newAddItemStatementRequestBuilder(
			redirectSource,
			validStatementSerialization
		).makeRequest();

		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newAddItemStatementRequestBuilder( itemId, validStatementSerialization )
			.withHeader( 'If-Unmodified-Since', yesterday )
			.makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSchema;
	} );
} );
