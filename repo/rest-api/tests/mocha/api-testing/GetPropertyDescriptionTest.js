'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
const { newGetPropertyDescriptionRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { utils } = require( 'api-testing' );

describe( newGetPropertyDescriptionRequestBuilder().getRouteDescription(), () => {
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
		const response = await newGetPropertyDescriptionRequestBuilder( propertyId, 'en' )
			.assertValidRequest().makeRequest();

		expect( response ).to.have.status( 200 );
		assert.strictEqual( response.body, propertyEnDescription );
	} );

} );
