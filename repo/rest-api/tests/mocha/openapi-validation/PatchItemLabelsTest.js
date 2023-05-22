'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
const { newPatchItemLabelsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newPatchItemLabelsRequestBuilder().getRouteDescription(), () => {

	let itemId;
	const langWithExistingLabel = 'en';

	function makeReplaceExistingLabelOp() {
		return {
			op: 'replace',
			path: `/${langWithExistingLabel}`,
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
		const response = await newPatchItemLabelsRequestBuilder(
			itemId,
			[ makeReplaceExistingLabelOp() ]
		).makeRequest();
		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 - invalid patch', async () => {
		const response = await newPatchItemLabelsRequestBuilder(
			itemId,
			[ { invalid: 'patch' } ]
		).makeRequest();
		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 - item not found', async () => {
		const response = await newPatchItemLabelsRequestBuilder(
			'Q999999',
			[ makeReplaceExistingLabelOp() ]
		).makeRequest();
		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '409 - patch test failed', async () => {
		const response = await newPatchItemLabelsRequestBuilder(
			itemId,
			[ { op: 'test', path: '/en', value: 'unexpected label!' } ]
		).makeRequest();
		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newPatchItemLabelsRequestBuilder(
			itemId,
			[ makeReplaceExistingLabelOp() ]
		).withHeader( 'If-Unmodified-Since', yesterday ).makeRequest();
		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '415 - unsupported media type', async () => {
		const response = await newPatchItemLabelsRequestBuilder(
			itemId,
			[ makeReplaceExistingLabelOp() ]
		).withHeader( 'Content-Type', 'text/plain' ).makeRequest();
		expect( response ).to.have.status( 415 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '422 - empty label', async () => {
		const response = await newPatchItemLabelsRequestBuilder(
			itemId,
			[ { op: 'replace', path: `/${langWithExistingLabel}`, value: '' } ]
		).makeRequest();
		expect( response ).to.have.status( 422 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
