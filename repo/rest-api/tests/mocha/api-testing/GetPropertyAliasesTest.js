'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
const { newGetPropertyAliasesRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetPropertyAliasesRequestBuilder().getRouteDescription(), () => {

	let propertyId;

	before( async () => {
		const testProperty = await createEntity( 'property', {
			aliases: {
				en: [
					{ language: 'en', value: 'en-alias1' },
					{ language: 'en', value: 'en-alias2' }
				]
			},
			datatype: 'string'
		} );
		propertyId = testProperty.entity.id;
	} );

	it( 'can get the aliases of a property', async () => {
		const response = await newGetPropertyAliasesRequestBuilder( propertyId ).assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual( response.body, { en: [ 'en-alias1', 'en-alias2' ] } );
	} );

} );
