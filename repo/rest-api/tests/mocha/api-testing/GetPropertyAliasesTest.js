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

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'property' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	it( '400 error - bad request, invalid property ID', async () => {
		const response = await newGetPropertyAliasesRequestBuilder( 'X123' )
			.assertInvalidRequest()
			.makeRequest();

		assertValidError(
			response,
			400,
			'invalid-path-parameter',
			{ parameter: 'property_id' }
		);
	} );

} );
