'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
const { newPatchPropertyLabelsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newPatchPropertyLabelsRequestBuilder().getRouteDescription(), () => {

	let propertyId;
	const langWithExistingLabel = 'en';

	function makeReplaceExistingLabelOp() {
		return {
			op: 'replace',
			path: `/${langWithExistingLabel}`,
			value: `test-label-${utils.uniq()}`
		};
	}

	before( async () => {
		const createPropertyResponse = await createEntity( 'property', {
			labels: [ {
				language: langWithExistingLabel,
				value: `test-label-${utils.uniq()}`
			} ],
			datatype: 'string'
		} );

		propertyId = createPropertyResponse.entity.id;
	} );

	it( '200 OK', async () => {
		const response = await newPatchPropertyLabelsRequestBuilder(
			propertyId,
			[ makeReplaceExistingLabelOp() ]
		).makeRequest();
		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 - invalid patch', async () => {
		const response = await newPatchPropertyLabelsRequestBuilder(
			propertyId,
			[ { invalid: 'patch' } ]
		).makeRequest();
		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 - property not found', async () => {
		const response = await newPatchPropertyLabelsRequestBuilder(
			'P999999',
			[ makeReplaceExistingLabelOp() ]
		).makeRequest();
		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '409 - patch test failed', async () => {
		const response = await newPatchPropertyLabelsRequestBuilder(
			propertyId,
			[ { op: 'test', path: '/en', value: 'unexpected label!' } ]
		).makeRequest();
		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newPatchPropertyLabelsRequestBuilder(
			propertyId,
			[ makeReplaceExistingLabelOp() ]
		).withHeader( 'If-Unmodified-Since', yesterday ).makeRequest();
		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '422 - empty label', async () => {
		const response = await newPatchPropertyLabelsRequestBuilder(
			propertyId,
			[ { op: 'replace', path: `/${langWithExistingLabel}`, value: '' } ]
		).makeRequest();
		expect( response ).to.have.status( 422 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
