'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
const {
	newRemovePropertyLabelRequestBuilder,
	newSetPropertyLabelRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

function makeUnique( text ) {
	return `${text}-${utils.uniq()}`;
}

describe( newRemovePropertyLabelRequestBuilder().getRouteDescription(), () => {

	let existingPropertyId;

	before( async () => {
		const createPropertyResponse = await createEntity( 'property', {
			labels: [ { language: 'en', value: makeUnique( 'unique label' ) } ],
			datatype: 'string'
		} );

		existingPropertyId = createPropertyResponse.entity.id;
	} );

	describe( '200 OK', () => {
		after( async () => {
			// replace removed label
			await newSetPropertyLabelRequestBuilder( existingPropertyId, 'en', makeUnique( 'updated label' ) )
				.makeRequest();
		} );

		it( 'label removed', async () => {
			const response = await newRemovePropertyLabelRequestBuilder( existingPropertyId, 'en' ).makeRequest();
			expect( response ).to.have.status( 200 );
			expect( response ).to.satisfyApiSpec;
		} );
	} );

	it( '400 - invalid language code', async () => {
		const response = await newRemovePropertyLabelRequestBuilder( existingPropertyId, 'xyz' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 - property does not exist', async () => {
		const response = await newRemovePropertyLabelRequestBuilder( 'P9999999', 'en' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newRemovePropertyLabelRequestBuilder( existingPropertyId, 'en' )
			.withHeader( 'If-Unmodified-Since', yesterday ).makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '415 - unsupported media type', async () => {
		const response = await newRemovePropertyLabelRequestBuilder( existingPropertyId, 'en' )
			.withHeader( 'Content-Type', 'text/plain' ).makeRequest();

		expect( response ).to.have.status( 415 );
		expect( response ).to.satisfyApiSpec;
	} );
} );
