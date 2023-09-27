'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity, getLatestEditMetadata } = require( '../helpers/entityHelper' );
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
		const testPropertyCreationMetadata = await getLatestEditMetadata( propertyId );

		const response = await newGetPropertyAliasesInLanguageRequestBuilder( propertyId, 'en' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual( response.body, [ 'example of', 'is a' ] );
		assert.strictEqual( response.header.etag, `"${testPropertyCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testPropertyCreationMetadata.timestamp );
	} );

	it( 'responds 404 in case the property does not exist', async () => {
		const nonExistentProperty = 'P99999999';
		const response = await newGetPropertyAliasesInLanguageRequestBuilder( nonExistentProperty, 'en' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'property-not-found' );
		assert.include( response.body.message, nonExistentProperty );
	} );
} );
