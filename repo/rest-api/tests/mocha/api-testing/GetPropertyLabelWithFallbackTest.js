'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { newGetPropertyLabelWithFallbackRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newGetPropertyLabelWithFallbackRequestBuilder().getRouteDescription(), () => {
	let propertyId;
	const propertyEnLabel = `en-label-${utils.uniq()}`;
	const fallbackLanguageWithExistingLabel = 'en';

	before( async () => {
		const testProperty = await createEntity( 'property', {
			labels: [ { language: fallbackLanguageWithExistingLabel, value: propertyEnLabel } ],
			datatype: 'string'
		} );
		propertyId = testProperty.entity.id;
	} );

	it( 'can get a label of a property', async () => {
		const response = await newGetPropertyLabelWithFallbackRequestBuilder( propertyId, 'en' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.strictEqual( response.body, propertyEnLabel );

		const testPropertyCreationMetadata = await getLatestEditMetadata( propertyId );
		assert.strictEqual( response.header.etag, `"${testPropertyCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testPropertyCreationMetadata.timestamp );
	} );

	it( 'responds 404 if the property does not exist', async () => {
		const nonExistentProperty = 'P99999999';
		const response = await newGetPropertyLabelWithFallbackRequestBuilder( nonExistentProperty, 'en' )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'property' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	it( 'responds 404 if the label does not exist in the requested or any fallback languages', async () => {
		const propertyWithoutFallback = await createEntity( 'property', {
			labels: { de: { language: 'de', value: `de-label-${utils.uniq()}` } },
			datatype: 'string'
		} );

		const response = await newGetPropertyLabelWithFallbackRequestBuilder(
			propertyWithoutFallback.entity.id,
			'ko'
		).assertValidRequest().makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'label' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	it( '307 - language fallback redirect', async () => {
		const languageCodeWithFallback = 'en-ca';

		const response = await newGetPropertyLabelWithFallbackRequestBuilder( propertyId, languageCodeWithFallback )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 307 );

		assert.isTrue( new URL( response.headers.location ).pathname.endsWith(
			`rest.php/wikibase/v0/entities/properties/${propertyId}/labels/${fallbackLanguageWithExistingLabel}`
		) );
	} );

	it( '400 - invalid property ID', async () => {
		const response = await newGetPropertyLabelWithFallbackRequestBuilder( 'X123', 'en' )
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
		const response = await newGetPropertyLabelWithFallbackRequestBuilder( propertyId, '1e' )
			.assertInvalidRequest()
			.makeRequest();

		assertValidError(
			response,
			400,
			'invalid-path-parameter',
			{ parameter: 'language_code' }
		);
	} );

} );
