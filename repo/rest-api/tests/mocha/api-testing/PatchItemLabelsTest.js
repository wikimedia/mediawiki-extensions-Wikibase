'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newPatchItemLabelsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newPatchItemLabelsRequestBuilder().getRouteDescription(), () => {
	let testItemId;

	before( async function () {
		testItemId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
	} );

	describe( '200 OK', () => {
		it( 'can add a label', async () => {
			const label = `new english label ${utils.uniq()}`;
			const response = await newPatchItemLabelsRequestBuilder(
				testItemId,
				[
					{ op: 'add', path: '/en', value: label }
				]
			).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.en, label );
		} );
	} );

} );
