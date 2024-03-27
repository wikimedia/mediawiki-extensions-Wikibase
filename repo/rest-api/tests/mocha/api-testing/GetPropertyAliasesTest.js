'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { newGetPropertyAliasesRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { assertValidError } = require( '../helpers/responseValidator' );

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
		const testPropertyCreationMetadata = await getLatestEditMetadata( propertyId );

		const response = await newGetPropertyAliasesRequestBuilder( propertyId ).assertValidRequest().makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual( response.body, { en: [ 'en-alias1', 'en-alias2' ] } );
		assert.strictEqual( response.header.etag, `"${testPropertyCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testPropertyCreationMetadata.timestamp );
	} );

	it( 'responds 404 in case the property does not exist', async () => {
		const nonExistentProperty = 'P99999999';
		const response = await newGetPropertyAliasesRequestBuilder( nonExistentProperty )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'property-not-found' );
		assert.include( response.body.message, nonExistentProperty );
	} );

	it( '400 error - bad request, invalid property ID', async () => {
		const invalidPropertyId = 'X123';
		const response = await newGetPropertyAliasesRequestBuilder( invalidPropertyId )
			.assertInvalidRequest()
			.makeRequest();

		assertValidError( response, 400, 'invalid-property-id', { 'property-id': invalidPropertyId } );
		assert.include( response.body.message, invalidPropertyId );
	} );

} );
