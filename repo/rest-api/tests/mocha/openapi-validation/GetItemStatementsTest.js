'use strict';

// allow chai expectations
/* eslint-disable no-unused-expressions */
const { REST } = require( 'api-testing' );
const chai = require( 'chai' );
const { createEntity, createSingleItem, createRedirectForItem } = require( '../helpers/entityHelper' );
const expect = chai.expect;
const basePath = 'rest.php/wikibase/v0';
const rest = new REST( basePath );

describe( 'validate GET /entities/items/{id}/statements responses against OpenAPI spec', () => {

	it( '200 OK response is valid for an Item with no statements', async () => {
		const createEmptyItemResponse = await createEntity( 'item', {} );
		const response = await rest.get( `/entities/items/${createEmptyItemResponse.entity.id}/statements` );

		expect( response.status ).to.equal( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '200 OK response is valid for an Item with statements', async () => {
		const createSingleItemResponse = await createSingleItem();
		const response = await rest.get( `/entities/items/${createSingleItemResponse.entity.id}/statements` );

		expect( response.status ).to.equal( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectTargetId = ( await createEntity( 'item', {} ) ).entity.id;
		const redirectSourceId = await createRedirectForItem( redirectTargetId );

		const response = await rest.get( `/entities/items/${redirectSourceId}/statements` );

		expect( response.status ).to.equal( 308 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await rest.get( '/entities/items/X123/statements' );

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await rest.get( '/entities/items/Q99999/statements' );

		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
