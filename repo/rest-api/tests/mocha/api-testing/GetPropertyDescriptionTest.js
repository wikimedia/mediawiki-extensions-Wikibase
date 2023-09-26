'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity, getLatestEditMetadata } = require( '../helpers/entityHelper' );
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
		const testPropertyCreationMetadata = await getLatestEditMetadata( propertyId );
		const response = await newGetPropertyDescriptionRequestBuilder( propertyId, 'en' )
			.assertValidRequest().makeRequest();

		expect( response ).to.have.status( 200 );
		assert.strictEqual( response.body, propertyEnDescription );
		assert.strictEqual( response.header.etag, `"${testPropertyCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testPropertyCreationMetadata.timestamp );
	} );

	it( 'responds 404 in case the property does not exist', async () => {
		const nonExistentProperty = 'P99999999';
		const response = await newGetPropertyDescriptionRequestBuilder( nonExistentProperty, 'en' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'property-not-found' );
		assert.include( response.body.message, nonExistentProperty );
	} );

} );
