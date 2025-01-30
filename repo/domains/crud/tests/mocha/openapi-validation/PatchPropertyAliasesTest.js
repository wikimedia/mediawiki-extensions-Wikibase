'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { createUniqueStringProperty } = require( '../helpers/entityHelper' );
const { newPatchPropertyAliasesRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newPatchPropertyAliasesRequestBuilder().getRouteDescription(), () => {

	let propertyId;

	function makeAddNewAliasOp() {
		return {
			op: 'add',
			path: '/de',
			value: [ `test-alias-${utils.uniq()}` ]
		};
	}

	before( async () => {
		const createPropertyResponse = await createUniqueStringProperty();
		propertyId = createPropertyResponse.body.id;
	} );

	it( '200 OK', async () => {
		const response = await newPatchPropertyAliasesRequestBuilder(
			propertyId,
			[ makeAddNewAliasOp() ]
		).makeRequest();
		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 - invalid patch', async () => {
		const response = await newPatchPropertyAliasesRequestBuilder(
			propertyId,
			[ { invalid: 'patch' } ]
		).makeRequest();
		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 - property not found', async () => {
		const response = await newPatchPropertyAliasesRequestBuilder(
			'P999999',
			[ makeAddNewAliasOp() ]
		).makeRequest();
		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '409 - patch test failed', async () => {
		const response = await newPatchPropertyAliasesRequestBuilder(
			propertyId,
			[ { op: 'test', path: '/en', value: [ 'unexpected', 'list', 'of', 'aliases' ] } ]
		).makeRequest();
		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newPatchPropertyAliasesRequestBuilder(
			propertyId,
			[ makeAddNewAliasOp() ]
		).withHeader( 'If-Unmodified-Since', yesterday ).makeRequest();
		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '422 - empty alias', async () => {
		const response = await newPatchPropertyAliasesRequestBuilder(
			propertyId,
			[ { op: 'add', path: '/en', value: [ '' ] } ]
		).makeRequest();
		expect( response ).to.have.status( 422 );
		expect( response ).to.satisfyApiSchema;
	} );

} );
