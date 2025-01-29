'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { getLatestEditMetadata } = require( '../helpers/entityHelper' );
const {
	newGetPropertyLabelWithFallbackRequestBuilder,
	newSetPropertyLabelRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newGetPropertyLabelWithFallbackRequestBuilder().getRouteDescription(), () => {
	let propertyId;
	const propertyDeLabel = `de-label-${utils.uniq()}`;
	const fallbackLanguageWithExistingLabel = 'de';

	async function makeRequestWithMulHeader( requestBuilder ) {
		return requestBuilder.withConfigOverride( 'wgWBRepoSettings', { enableMulLanguageCode: true } )
			.assertValidRequest()
			.makeRequest();
	}

	before( async () => {
		const testProperty = await newCreatePropertyRequestBuilder(
			{ data_type: 'string', labels: { [ fallbackLanguageWithExistingLabel ]: propertyDeLabel } }
		).makeRequest();
		propertyId = testProperty.body.id;
	} );

	it( '200 - can get a label of a property', async () => {
		const response = await newGetPropertyLabelWithFallbackRequestBuilder( propertyId, fallbackLanguageWithExistingLabel )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.strictEqual( response.body, propertyDeLabel );

		const testPropertyCreationMetadata = await getLatestEditMetadata( propertyId );
		assert.strictEqual( response.header.etag, `"${testPropertyCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testPropertyCreationMetadata.timestamp );
	} );

	it( '307 - language fallback redirect', async () => {
		const languageCodeWithFallback = 'bar';

		const response = await makeRequestWithMulHeader(
			newGetPropertyLabelWithFallbackRequestBuilder( propertyId, languageCodeWithFallback )
		);

		expect( response ).to.have.status( 307 );

		assert.isTrue( new URL( response.headers.location ).pathname.endsWith(
			`rest.php/wikibase/v1/entities/properties/${propertyId}/labels/de`
		) );
	} );

	it( '307 - language fallback redirect mul', async () => {
		await makeRequestWithMulHeader( newSetPropertyLabelRequestBuilder( propertyId, 'mul', `mul-label-${utils.uniq()}` ) );

		const response = await makeRequestWithMulHeader( newGetPropertyLabelWithFallbackRequestBuilder( propertyId, 'en' ) );

		expect( response ).to.have.status( 307 );

		assert.isTrue( new URL( response.headers.location ).pathname.endsWith(
			`rest.php/wikibase/v1/entities/properties/${propertyId}/labels/mul`
		) );
	} );

	it( '400 - invalid property ID', async () => {
		const response = await newGetPropertyLabelWithFallbackRequestBuilder( 'X123', fallbackLanguageWithExistingLabel )
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

	it( '404 - in case property does not exist', async () => {
		const nonExistentProperty = 'P99999999';
		const response = await newGetPropertyLabelWithFallbackRequestBuilder(
			nonExistentProperty,
			fallbackLanguageWithExistingLabel
		).assertValidRequest().makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'property' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	it( '404 - in case label does not exist in the requested or any fallback languages', async () => {
		const propertyWithoutMulFallbackId = ( await newCreatePropertyRequestBuilder(
			{ data_type: 'string', labels: { ar: `ar-label-${utils.uniq()}` } }
		).makeRequest() ).body.id;

		const response = await makeRequestWithMulHeader(
			newGetPropertyLabelWithFallbackRequestBuilder( propertyWithoutMulFallbackId, 'en' )
		);

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'label' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );
} );
