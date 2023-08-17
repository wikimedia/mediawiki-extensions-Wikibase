'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const { createUniqueStringProperty } = require( '../helpers/entityHelper' );
const { newAddPropertyStatementRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( 'validate POST /entities/properties/{id}/statements', () => {

	let propertyId;
	let validStatementSerialization;

	before( async () => {
		propertyId = ( await createUniqueStringProperty() ).entity.id;
		validStatementSerialization = {
			value: { type: 'novalue' },
			property: { id: propertyId }
		};
	} );

	it( '201 Created is valid', async () => {
		const response = await newAddPropertyStatementRequestBuilder( propertyId, validStatementSerialization )
			.makeRequest();

		expect( response ).to.have.status( 201 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request is valid for invalid statement', async () => {
		const response = await newAddPropertyStatementRequestBuilder(
			propertyId,
			{ invalid: 'statement' }
		).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request is valid for invalid statement param type', async () => {
		const response = await newAddPropertyStatementRequestBuilder(
			propertyId,
			'invalid statement param type'
		).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found is valid for non-existent Property', async () => {
		const response = await newAddPropertyStatementRequestBuilder( 'P9999999', validStatementSerialization )
			.makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newAddPropertyStatementRequestBuilder( propertyId, validStatementSerialization )
			.withHeader( 'If-Unmodified-Since', yesterday )
			.makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '415 - unsupported media type', async () => {
		const response = await newAddPropertyStatementRequestBuilder( propertyId, validStatementSerialization )
			.withHeader( 'Content-Type', 'text/plain' )
			.makeRequest();

		expect( response ).to.have.status( 415 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
