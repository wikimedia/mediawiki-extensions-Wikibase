'use strict';

// allow chai expectations
/* eslint-disable no-unused-expressions */
const { REST, action } = require( 'api-testing' );
const SwaggerParser = require( '@apidevtools/swagger-parser' );
const entityHelper = require( '../helpers/entityHelper' );
const chai = require( 'chai' );
const expect = chai.expect;
const chaiResponseValidator = require( 'chai-openapi-response-validator' ).default;
const basePath = 'rest.php/wikibase/v0';
const rest = new REST( basePath );

describe( 'validate GET /entities/items/{id} responses against OpenAPI document', () => {
	before( async () => {
		const spec = await SwaggerParser.dereference( './specs/openapi.json' );
		// dynamically add CI test system to the spec
		spec.servers = [ { url: rest.req.app + basePath } ];
		chai.use( chaiResponseValidator( spec ) );
	} );

	it( '200 OK response is valid for an "empty" item', async () => {
		const createEmptyItemResponse = await entityHelper.createEntity( 'item', {} );
		const response = await rest.get( `/entities/items/${createEmptyItemResponse.entity.id}` );

		expect( response.status ).to.equal( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '200 OK response is valid for a non-empty item', async () => {
		const createSingleItemResponse = await entityHelper.createSingleItem();
		const response = await rest.get( `/entities/items/${createSingleItemResponse.entity.id}` );

		expect( response.status ).to.equal( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
		const redirectTargetId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
		await action.getAnon().action( 'wbcreateredirect', {
			from: redirectSourceId,
			to: redirectTargetId,
			token: '+\\'
		}, true );

		const response = await rest.get( `/entities/items/${redirectSourceId}` );

		expect( response.status ).to.equal( 308 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const createSingleItemResponse = await entityHelper.createSingleItem();
		const response = await rest.get(
			`/entities/items/${createSingleItemResponse.entity.id}`,
			{},
			{ 'If-None-Match': `"${createSingleItemResponse.entity.lastrevid}"` }
		);

		expect( response.status ).to.equal( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await rest.get( '/entities/items/X123' );

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid field', async () => {
		const response = await rest.get( '/entities/items/Q123', { _fields: 'unknown_field' } );

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await rest.get( '/entities/items/Q99999' );

		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
