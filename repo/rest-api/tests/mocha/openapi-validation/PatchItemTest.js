'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
const { newPatchItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newPatchItemRequestBuilder().getRouteDescription(), () => {

	const langWithExistingLabel = 'en';
	let itemId;

	function makeReplaceExistingLabelOp() {
		return {
			op: 'replace',
			path: `/labels/${langWithExistingLabel}`,
			value: `test-label-${utils.uniq()}`
		};
	}

	before( async () => {
		const createItemResponse = await createEntity( 'item', {
			labels: [ {
				language: langWithExistingLabel,
				value: `test-label-${utils.uniq()}`
			} ]
		} );
		itemId = createItemResponse.entity.id;
	} );

	it( '200 OK', async () => {
		const response = await newPatchItemRequestBuilder(
			itemId,
			[ makeReplaceExistingLabelOp() ]
		).makeRequest();
		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 - invalid patch', async () => {
		const response = await newPatchItemRequestBuilder(
			itemId,
			[ { invalid: 'patch' } ]
		).makeRequest();
		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 - item not found', async () => {
		const response = await newPatchItemRequestBuilder(
			'Q999999',
			[ makeReplaceExistingLabelOp() ]
		).makeRequest();
		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '409 - patch test failed', async () => {
		const response = await newPatchItemRequestBuilder(
			itemId,
			[ { op: 'test', path: `/labels/${langWithExistingLabel}`, value: 'unexpected label!' } ]
		).makeRequest();
		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newPatchItemRequestBuilder(
			itemId,
			[ makeReplaceExistingLabelOp() ]
		).withHeader( 'If-Unmodified-Since', yesterday ).makeRequest();
		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '422 - invalid field', async () => {
		const response = await newPatchItemRequestBuilder(
			itemId,
			[ { op: 'add', path: '/descriptions/de', value: 42 } ]
		).makeRequest();
		expect( response ).to.have.status( 422 );
		expect( response ).to.satisfyApiSpec;
	} );
} );
