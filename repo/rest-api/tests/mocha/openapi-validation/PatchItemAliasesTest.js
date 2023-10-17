'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
const { newPatchItemAliasesRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newPatchItemAliasesRequestBuilder().getRouteDescription(), () => {

	const langWithExistingAliases = 'en';
	let itemId;

	function makeAddNewAliasOp() {
		return {
			op: 'add',
			path: `/${langWithExistingAliases}/-`,
			value: `test-alias-${utils.uniq()}`
		};
	}

	before( async () => {
		const createItemResponse = await createEntity( 'item', {
			aliases: {
				[ langWithExistingAliases ]: [
					{ language: langWithExistingAliases, value: `test-aliases-${utils.uniq()}` },
					{ language: langWithExistingAliases, value: `test-aliases-${utils.uniq()}` }
				]
			}
		} );

		itemId = createItemResponse.entity.id;
	} );

	it( '200 OK', async () => {
		const response = await newPatchItemAliasesRequestBuilder( itemId, [ makeAddNewAliasOp() ] )
			.makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 - invalid patch', async () => {
		const response = await newPatchItemAliasesRequestBuilder( itemId, [ { invalid: 'patch' } ] )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 - item not found', async () => {
		const response = await newPatchItemAliasesRequestBuilder( 'Q999999', [ makeAddNewAliasOp() ] )
			.makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '409 - patch test failed', async () => {
		const response = await newPatchItemAliasesRequestBuilder(
			itemId,
			[ { op: 'test', path: '/en/0', value: 'unexpected alias!' } ]
		).makeRequest();

		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newPatchItemAliasesRequestBuilder(
			itemId,
			[ makeAddNewAliasOp() ]
		).withHeader( 'If-Unmodified-Since', yesterday ).makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '415 - unsupported media type', async () => {
		const response = await newPatchItemAliasesRequestBuilder(
			itemId,
			[ makeAddNewAliasOp() ]
		).withHeader( 'Content-Type', 'text/plain' ).makeRequest();

		expect( response ).to.have.status( 415 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '422 - empty alias', async () => {
		const response = await newPatchItemAliasesRequestBuilder(
			itemId,
			[ { op: 'add', path: `/${langWithExistingAliases}/-`, value: '' } ]
		).makeRequest();

		expect( response ).to.have.status( 422 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
