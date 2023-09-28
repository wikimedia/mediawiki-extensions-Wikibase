'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
const { newPatchItemDescriptionsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newPatchItemDescriptionsRequestBuilder().getRouteDescription(), () => {

	const langWithExistingDescription = 'en';
	let itemId;

	function makeReplaceExistingDescriptionOp() {
		return {
			op: 'replace',
			path: `/${langWithExistingDescription}`,
			value: `test-description-${utils.uniq()}`
		};
	}

	before( async () => {
		const createItemResponse = await createEntity( 'item', {
			descriptions: [ {
				language: langWithExistingDescription,
				value: `test-description-${utils.uniq()}`
			} ]
		} );

		itemId = createItemResponse.entity.id;
	} );

	it( '200 OK', async () => {
		const response = await newPatchItemDescriptionsRequestBuilder(
			itemId,
			[ makeReplaceExistingDescriptionOp() ]
		).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 - invalid patch', async () => {
		const response = await newPatchItemDescriptionsRequestBuilder(
			itemId,
			[ { invalid: 'patch' } ]
		).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 - item not found', async () => {
		const response = await newPatchItemDescriptionsRequestBuilder(
			'Q999999',
			[ makeReplaceExistingDescriptionOp() ]
		).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '409 - patch test failed', async () => {
		const response = await newPatchItemDescriptionsRequestBuilder(
			itemId,
			[ { op: 'test', path: '/en', value: 'unexpected description!' } ]
		).makeRequest();

		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newPatchItemDescriptionsRequestBuilder(
			itemId,
			[ makeReplaceExistingDescriptionOp() ]
		).withHeader( 'If-Unmodified-Since', yesterday ).makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '415 - unsupported media type', async () => {
		const response = await newPatchItemDescriptionsRequestBuilder(
			itemId,
			[ makeReplaceExistingDescriptionOp() ]
		).withHeader( 'Content-Type', 'text/plain' ).makeRequest();

		expect( response ).to.have.status( 415 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '422 - empty description', async () => {
		const response = await newPatchItemDescriptionsRequestBuilder(
			itemId,
			[ { op: 'replace', path: `/${langWithExistingDescription}`, value: '' } ]
		).makeRequest();

		expect( response ).to.have.status( 422 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
