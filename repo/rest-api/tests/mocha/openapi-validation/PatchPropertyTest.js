'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createUniqueStringProperty } = require( '../helpers/entityHelper' );
const { newPatchPropertyRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newPatchPropertyRequestBuilder().getRouteDescription(), () => {

	let propertyId;

	function makeAddNewLabelOp() {
		return {
			op: 'add',
			path: '/labels/de',
			value: `test-label-${utils.uniq()}`
		};
	}

	before( async () => {
		const createPropertyResponse = await createUniqueStringProperty();
		propertyId = createPropertyResponse.entity.id;
	} );

	it( '200 OK', async () => {
		const response = await newPatchPropertyRequestBuilder( propertyId, [ makeAddNewLabelOp() ] )
			.makeRequest();
		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 - invalid patch', async () => {
		const response = await newPatchPropertyRequestBuilder( propertyId, [ { invalid: 'patch' } ] )
			.makeRequest();
		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 - property not found', async () => {
		const response = await newPatchPropertyRequestBuilder( 'P999999', [ makeAddNewLabelOp() ] )
			.makeRequest();
		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '409 - patch test failed', async () => {
		const response = await newPatchPropertyRequestBuilder(
			propertyId,
			[ { op: 'test', path: '/aliases/en', value: [ 'unexpected', 'list', 'of', 'aliases' ] } ]
		).makeRequest();
		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newPatchPropertyRequestBuilder( propertyId, [ makeAddNewLabelOp() ] )
			.withHeader( 'If-Unmodified-Since', yesterday )
			.makeRequest();
		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '422 - unexpected field', async () => {
		const response = await newPatchPropertyRequestBuilder(
			propertyId,
			[ { op: 'add', path: '/foo', value: 'bar' } ]
		).makeRequest();
		expect( response ).to.have.status( 422 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
