'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
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
	} );

} );
