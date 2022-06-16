'use strict';

const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const { createUniqueStringProperty, createEntity } = require( '../helpers/entityHelper' );
const expect = require( 'chai' ).expect;

function newAddItemStatementRequestBuilder( itemId, statement ) {
	return new RequestBuilder()
		.withRoute( '/entities/items/{item_id}/statements' )
		.withPathParam( 'item_id', itemId )
		.withJsonBodyParam( 'statement', statement );
}

describe( 'validate POST /entities/items/{id}/statements', () => {

	let validStatementSerialization;
	let itemId;

	before( async () => {
		itemId = ( await createEntity( 'item', {} ) ).entity.id;
		const propertyId = ( await createUniqueStringProperty() ).entity.id;
		validStatementSerialization = {
			type: 'statement',
			mainsnak: {
				snaktype: 'novalue',
				property: propertyId
			}
		};
	} );

	it( '201 Created is valid', async () => {
		const response = await newAddItemStatementRequestBuilder(
			itemId,
			validStatementSerialization
		).makeRequest( 'POST' );

		expect( response.status ).to.equal( 201 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found is valid for non-existent Item', async () => {
		const response = await newAddItemStatementRequestBuilder( 'Q9999999', validStatementSerialization )
			.makeRequest( 'POST' );

		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request is valid for invalid statement', async () => {
		const response = await newAddItemStatementRequestBuilder(
			itemId,
			{ invalid: 'statement' }
		).makeRequest( 'POST' );

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	// T310783
	it.skip( '400 Bad Request is valid for invalid statement param type', async () => {
		const response = await newAddItemStatementRequestBuilder(
			itemId,
			'invalid statement param type'
		).makeRequest( 'POST' );

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
