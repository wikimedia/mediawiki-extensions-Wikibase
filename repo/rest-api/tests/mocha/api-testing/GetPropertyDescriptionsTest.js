'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { newGetPropertyDescriptionsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { utils } = require( 'api-testing' );

describe( newGetPropertyDescriptionsRequestBuilder().getRouteDescription(), () => {
	let propertyId;
	let propertyDescriptions;

	before( async () => {
		const testProperty = await createEntity( 'property', {
			labels: { en: { language: 'en', value: `string-property-${utils.uniq()}` } },
			descriptions: {
				en: { language: 'en', value: `string-property-description-${utils.uniq()}` },
				de: { language: 'de', value: `string-Eigenschaft-Beschreibung-${utils.uniq()}` }
			},
			datatype: 'string'
		} );

		propertyId = testProperty.entity.id;
		propertyDescriptions = testProperty.entity.descriptions;
	} );

	it( 'can get the descriptions of a property', async () => {
		const testPropertyCreationMetadata = await getLatestEditMetadata( propertyId );
		const response = await newGetPropertyDescriptionsRequestBuilder( propertyId )
			.assertValidRequest().makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual(
			response.body,
			{
				en: propertyDescriptions.en.value,
				de: propertyDescriptions.de.value
			}
		);
		assert.strictEqual( response.header.etag, `"${testPropertyCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testPropertyCreationMetadata.timestamp );
	} );

	it( 'responds 404 in case the property does not exist', async () => {
		const nonExistentProperty = 'P99999999';
		const response = await newGetPropertyDescriptionsRequestBuilder( nonExistentProperty )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'property-not-found' );
		assert.include( response.body.message, nonExistentProperty );
	} );

} );
