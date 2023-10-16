'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
const { newSetPropertyDescriptionRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

function makeUnique( text ) {
	return `${text}-${utils.uniq()}`;
}

describe( newSetPropertyDescriptionRequestBuilder().getRouteDescription(), () => {

	let existingPropertyId;

	before( async () => {
		const createPropertyResponse = await createEntity( 'property', {
			descriptions: [ {
				language: 'en',
				value: makeUnique( 'unique description' )
			} ],
			datatype: 'string'
		} );

		existingPropertyId = createPropertyResponse.entity.id;
	} );

	it( '200 - description replaced', async () => {
		const response = await newSetPropertyDescriptionRequestBuilder(
			existingPropertyId,
			'en',
			makeUnique( 'updated description' )
		).makeRequest();
		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '201 - description created', async () => {
		const response = await newSetPropertyDescriptionRequestBuilder(
			existingPropertyId,
			'de',
			makeUnique( 'neue Beschreibung' )
		).makeRequest();
		expect( response ).to.have.status( 201 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 - invalid description', async () => {
		const response = await newSetPropertyDescriptionRequestBuilder(
			existingPropertyId,
			'en',
			'tab character \t not allowed'
		).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 - property does not exist', async () => {
		const response = await newSetPropertyDescriptionRequestBuilder(
			'P9999999',
			'en',
			makeUnique( 'updated description' )
		).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newSetPropertyDescriptionRequestBuilder(
			existingPropertyId,
			'en',
			makeUnique( 'updated description' )
		)
			.withHeader( 'If-Unmodified-Since', yesterday )
			.makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '415 - unsupported media type', async () => {
		const response = await newSetPropertyDescriptionRequestBuilder(
			existingPropertyId,
			'en',
			makeUnique( 'updated description' )
		)
			.withHeader( 'Content-Type', 'text/plain' )
			.makeRequest();

		expect( response ).to.have.status( 415 );
		expect( response ).to.satisfyApiSpec;
	} );
} );
