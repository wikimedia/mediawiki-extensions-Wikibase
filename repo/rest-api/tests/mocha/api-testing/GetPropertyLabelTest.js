'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { newGetPropertyLabelRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetPropertyLabelRequestBuilder().getRouteDescription(), () => {
	let propertyId;
	const propertyEnLabel = `en-label-${utils.uniq()}`;

	before( async () => {
		const testProperty = await createEntity( 'property', {
			labels: [ { language: 'en', value: propertyEnLabel } ],
			datatype: 'string'
		} );
		propertyId = testProperty.entity.id;
	} );

	it( 'can get a label of a property', async () => {
		const response = await newGetPropertyLabelRequestBuilder( propertyId, 'en' ).assertValidRequest().makeRequest();

		expect( response ).to.have.status( 200 );
		assert.strictEqual( response.body, propertyEnLabel );

		const testPropertyCreationMetadata = await getLatestEditMetadata( propertyId );
		assert.strictEqual( response.header.etag, `"${testPropertyCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testPropertyCreationMetadata.timestamp );
	} );

	it( 'responds 404 if the property does not exist', async () => {
		const nonExistentProperty = 'P99999999';
		const response = await newGetPropertyLabelRequestBuilder( nonExistentProperty, 'en' )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'property-not-found' );
		assert.include( response.body.message, nonExistentProperty );
	} );

	it( 'responds 404 if the label does not exist', async () => {
		const languageCodeWithNoDefinedLabel = 'ko';
		const response = await newGetPropertyLabelRequestBuilder( propertyId, languageCodeWithNoDefinedLabel )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'label-not-defined' );
		assert.include( response.body.message, propertyId );
		assert.include( response.body.message, languageCodeWithNoDefinedLabel );
	} );

	it( '400 - invalid property ID', async () => {
		const invalidPropertyId = 'X123';
		const response = await newGetPropertyLabelRequestBuilder( invalidPropertyId, 'en' )
			.assertInvalidRequest()
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'invalid-property-id' );
		assert.include( response.body.message, invalidPropertyId );
	} );

	it( '400 - invalid language code', async () => {
		const invalidLanguageCode = '1e';
		const response = await newGetPropertyLabelRequestBuilder( propertyId, invalidLanguageCode )
			.assertInvalidRequest()
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'invalid-language-code' );
		assert.include( response.body.message, invalidLanguageCode );
	} );

} );
