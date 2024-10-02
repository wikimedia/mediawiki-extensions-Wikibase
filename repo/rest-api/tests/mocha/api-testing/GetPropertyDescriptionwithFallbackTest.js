'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { newGetPropertyDescriptionWithFallbackRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { utils } = require( 'api-testing' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newGetPropertyDescriptionWithFallbackRequestBuilder().getRouteDescription(), () => {
	let propertyId;
	const propertyEnDescription = `string-property-description-${utils.uniq()}`;

	before( async () => {
		const testProperty = await createEntity( 'property', {
			labels: { en: { language: 'en', value: `string-property-${utils.uniq()}` } },
			descriptions: {
				en: { language: 'en', value: propertyEnDescription }
			},
			datatype: 'string'
		} );

		propertyId = testProperty.entity.id;
	} );

	it( 'can get a language specific description of a property', async () => {
		const testPropertyCreationMetadata = await getLatestEditMetadata( propertyId );
		const response = await newGetPropertyDescriptionWithFallbackRequestBuilder( propertyId, 'en' )
			.assertValidRequest().makeRequest();

		expect( response ).to.have.status( 200 );
		assert.strictEqual( response.body, propertyEnDescription );
		assert.strictEqual( response.header.etag, `"${testPropertyCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testPropertyCreationMetadata.timestamp );
	} );

	it( '400 - invalid property ID', async () => {
		const response = await newGetPropertyDescriptionWithFallbackRequestBuilder( 'X123', 'en' )
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
		const response = await newGetPropertyDescriptionWithFallbackRequestBuilder( propertyId, '1e' )
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
		const response = await newGetPropertyDescriptionWithFallbackRequestBuilder( nonExistentProperty, 'en' )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'property' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	it( 'responds 404 in case the property has no description in the requested language', async () => {
		const languageCode = 'ko';
		const response = await newGetPropertyDescriptionWithFallbackRequestBuilder( propertyId, languageCode )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'description' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

} );
