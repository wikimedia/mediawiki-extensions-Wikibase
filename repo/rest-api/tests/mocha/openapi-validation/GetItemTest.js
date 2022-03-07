'use strict';

// allow chai expectations
/* eslint-disable no-unused-expressions */
const { REST } = require( 'api-testing' );
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

} );
