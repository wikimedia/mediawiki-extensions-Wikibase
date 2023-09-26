'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
const { newGetPropertyAliasesInLanguageRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetPropertyAliasesInLanguageRequestBuilder().getRouteDescription(), () => {
	let propertyId;

	before( async () => {
		const createPropertyResponse = await createEntity( 'property', {
			aliases: {
				en: [
					{ language: 'en', value: 'example of' },
					{ language: 'en', value: 'is a' }
				]
			},
			datatype: 'string'
		} );

		propertyId = createPropertyResponse.entity.id;
	} );

	it( 'can get language specific aliases of a property', async () => {
		const response = await newGetPropertyAliasesInLanguageRequestBuilder( propertyId, 'en' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual( response.body, [ 'example of', 'is a' ] );
	} );
} );
