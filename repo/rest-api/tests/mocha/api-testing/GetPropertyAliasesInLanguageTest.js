'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { newGetPropertyAliasesInLanguageRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { assertValidError } = require( '../helpers/responseValidator' );

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

	it( '400 - invalid property ID', async () => {
		const response = await newGetPropertyAliasesInLanguageRequestBuilder( 'X123', 'en' )
			.assertInvalidRequest()
			.makeRequest();

		assertValidError(
			response,
			400,
			'invalid-path-parameter',
			{ parameter: 'property_id' }
		);
	} );

	it( '400 - invalid language code', async () => {
		const response = await newGetPropertyAliasesInLanguageRequestBuilder( propertyId, '1e' )
			.assertInvalidRequest()
			.makeRequest();

		assertValidError(
			response,
			400,
			'invalid-path-parameter',
			{ parameter: 'language_code' }
		);
	} );

	it( 'responds 404 in case the property does not exist', async () => {
		const nonExistentProperty = 'P99999999';
		const response = await newGetPropertyAliasesInLanguageRequestBuilder( nonExistentProperty, 'en' )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'property-not-found' );
		assert.include( response.body.message, nonExistentProperty );
	} );

	it( 'responds 404 in case the property has no aliases in the requested language', async () => {
		const languageCode = 'ar';
		const response = await newGetPropertyAliasesInLanguageRequestBuilder( propertyId, languageCode )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'aliases' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );
} );
