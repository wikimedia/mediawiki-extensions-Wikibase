'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
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
	} );

} );
