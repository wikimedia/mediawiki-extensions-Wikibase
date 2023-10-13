'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
const { newSetPropertyLabelRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

function makeLabel( text ) {
	return `${text}-${utils.uniq()}`;
}

describe( newSetPropertyLabelRequestBuilder().getRouteDescription(), () => {

	let propertyId;
	const langWithExistingLabel = 'en';

	before( async () => {
		const createPropertyResponse = await createEntity( 'property', {
			labels: [ {
				language: langWithExistingLabel,
				value: makeLabel( 'en-label' )
			} ],
			datatype: 'string'
		} );

		propertyId = createPropertyResponse.entity.id;
	} );

	it( '200 - label replaced', async () => {
		const response = await newSetPropertyLabelRequestBuilder(
			propertyId,
			langWithExistingLabel,
			makeLabel( 'updated label' )
		).makeRequest();
		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '201 - label created', async () => {
		const response = await newSetPropertyLabelRequestBuilder(
			propertyId,
			'de',
			makeLabel( 'neue Beschreibung' )
		).makeRequest();
		expect( response ).to.have.status( 201 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 - invalid (empty) label', async () => {
		const response = await newSetPropertyLabelRequestBuilder( propertyId, 'de', '' )
			.makeRequest();
		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 - property does not exist', async () => {
		const response = await newSetPropertyLabelRequestBuilder(
			'P9999999',
			langWithExistingLabel,
			makeLabel( 'updated label' )
		).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newSetPropertyLabelRequestBuilder(
			propertyId,
			langWithExistingLabel,
			makeLabel( 'updated label' )
		)
			.withHeader( 'If-Unmodified-Since', yesterday )
			.makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '415 - unsupported media type', async () => {
		const response = await newSetPropertyLabelRequestBuilder(
			propertyId,
			langWithExistingLabel,
			makeLabel( 'updated label' )
		)
			.withHeader( 'Content-Type', 'text/plain' )
			.makeRequest();

		expect( response ).to.have.status( 415 );
		expect( response ).to.satisfyApiSpec;
	} );
} );
