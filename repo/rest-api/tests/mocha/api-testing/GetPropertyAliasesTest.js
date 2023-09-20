'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const { newGetPropertyAliasesRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetPropertyAliasesRequestBuilder().getRouteDescription(), () => {

	let propertyId;

	before( async () => {
		const testProperty = await createEntity( 'property', {
			aliases: {
				en: [
					{ language: 'en', value: 'en-alias1' },
					{ language: 'en', value: 'en-alias2' }
				]
			},
			datatype: 'string'
		} );
		propertyId = testProperty.entity.id;
	} );

	it( 'can get the aliases of a property', async () => {
		const testPropertyCreationMetadata = await getLatestEditMetadata( propertyId );

		const response = await newGetPropertyAliasesRequestBuilder( propertyId ).assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual( response.body, { en: [ 'en-alias1', 'en-alias2' ] } );
		assert.strictEqual( response.header.etag, `"${testPropertyCreationMetadata.revid}"` );
		assert.strictEqual( response.header[ 'last-modified' ], testPropertyCreationMetadata.timestamp );
	} );

	it( 'responds 404 in case the property does not exist', async () => {
		const nonExistentProperty = 'P99999999';
		const response = await newGetPropertyAliasesRequestBuilder( nonExistentProperty )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'property-not-found' );
		assert.include( response.body.message, nonExistentProperty );
	} );

} );
