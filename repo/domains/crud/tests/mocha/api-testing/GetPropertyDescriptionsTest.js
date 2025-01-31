'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { getLatestEditMetadata } = require( '../helpers/entityHelper' );
const {
	newGetPropertyDescriptionsRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { utils } = require( 'api-testing' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newGetPropertyDescriptionsRequestBuilder().getRouteDescription(), () => {
	let propertyId;
	let propertyDescriptions;

	before( async () => {
		const testProperty = await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			labels: { en: `string-property-${utils.uniq()}` },
			descriptions: {
				en: `string-property-description-${utils.uniq()}`,
				de: `string-Eigenschaft-Beschreibung-${utils.uniq()}`
			}
		} ).makeRequest();
		propertyId = testProperty.body.id;
		propertyDescriptions = testProperty.body.descriptions;
	} );

	it( 'can get the descriptions of a property', async () => {
		const testPropertyCreationMetadata = await getLatestEditMetadata( propertyId );
		const response = await newGetPropertyDescriptionsRequestBuilder( propertyId )
			.assertValidRequest().makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual(
			response.body,
			propertyDescriptions
		);
		assert.strictEqual( response.header.etag, `"${testPropertyCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testPropertyCreationMetadata.timestamp );
	} );

	it( 'responds 404 in case the property does not exist', async () => {
		const nonExistentProperty = 'P99999999';
		const response = await newGetPropertyDescriptionsRequestBuilder( nonExistentProperty )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'resource-not-found', { resource_type: 'property' } );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
	} );

	it( '400 error - bad request, invalid property ID', async () => {
		const response = await newGetPropertyDescriptionsRequestBuilder( 'X123' )
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
