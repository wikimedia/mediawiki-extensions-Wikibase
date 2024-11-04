'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { getLatestEditMetadata } = require( '../helpers/entityHelper' );
const {
	newGetPropertyDescriptionWithFallbackRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { utils } = require( 'api-testing' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newGetPropertyDescriptionWithFallbackRequestBuilder().getRouteDescription(), () => {
	let propertyId;
	const fallbackLanguageWithDescription = 'de';
	const propertyDeDescription = `string-property-description-${utils.uniq()}`;

	before( async () => {
		const testProperty = await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			labels: { en: `string-property-${utils.uniq()}` },
			descriptions: { [ fallbackLanguageWithDescription ]: propertyDeDescription }
		} ).makeRequest();
		propertyId = testProperty.body.id;
	} );

	it( '200 - can get a language specific description of a property', async () => {
		const testPropertyCreationMetadata = await getLatestEditMetadata( propertyId );
		const response = await newGetPropertyDescriptionWithFallbackRequestBuilder( propertyId, 'de' )
			.assertValidRequest().makeRequest();

		expect( response ).to.have.status( 200 );
		assert.strictEqual( response.body, propertyDeDescription );
		assert.strictEqual( response.header.etag, `"${testPropertyCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testPropertyCreationMetadata.timestamp );
	} );

	it( '307 - language fallback redirect', async () => {
		const response = await newGetPropertyDescriptionWithFallbackRequestBuilder( propertyId, 'bar' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 307 );
		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith(
					`rest.php/wikibase/v1/entities/properties/${propertyId}/descriptions/${fallbackLanguageWithDescription}`
				)
		);
	} );

	it( '400 - invalid property ID', async () => {
		const response = await newGetPropertyDescriptionWithFallbackRequestBuilder( 'X123', 'de' )
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

	it( '404 - in case the property does not exist', async () => {
		const nonExistentProperty = 'P99999999';
		const response = await newGetPropertyDescriptionWithFallbackRequestBuilder( nonExistentProperty, 'de' )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'property' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	it( '404 - in case the property has no description in the requested language', async () => {
		const languageCode = 'ko';
		const response = await newGetPropertyDescriptionWithFallbackRequestBuilder( propertyId, languageCode )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'description' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

} );
