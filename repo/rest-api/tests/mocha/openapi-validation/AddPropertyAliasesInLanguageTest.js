'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity } = require( '../helpers/entityHelper' );
const { newAddPropertyAliasesInLanguageRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

function makeUnique( text ) {
	return `${text}-${utils.uniq()}`;
}

describe( newAddPropertyAliasesInLanguageRequestBuilder().getRouteDescription(), () => {

	let existingPropertyId;
	const languageWithExistingAlias = 'en';

	before( async () => {
		const createPropertyResponse = await createEntity( 'property', {
			aliases: [ {
				language: languageWithExistingAlias,
				value: makeUnique( 'en-alias' )
			} ],
			datatype: 'string'
		} );

		existingPropertyId = createPropertyResponse.entity.id;
	} );

	it( '200 - added alias to existing ones', async () => {
		const response = await newAddPropertyAliasesInLanguageRequestBuilder(
			existingPropertyId,
			'en',
			[ makeUnique( 'added-en-alias' ) ]
		).makeRequest();
		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '201 - added alias where there were no existing ones', async () => {
		const response = await newAddPropertyAliasesInLanguageRequestBuilder(
			existingPropertyId,
			'de',
			[ makeUnique( 'first-de-alias' ) ]
		).makeRequest();
		expect( response ).to.have.status( 201 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 - invalid alias', async () => {
		const response = await newAddPropertyAliasesInLanguageRequestBuilder(
			existingPropertyId,
			'en',
			[ 'tab character \t not allowed' ]
		).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 - property does not exist', async () => {
		const response = await newAddPropertyAliasesInLanguageRequestBuilder(
			'P9999999',
			'en',
			[ makeUnique( 'some-alias' ) ]
		).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newAddPropertyAliasesInLanguageRequestBuilder(
			existingPropertyId,
			'en',
			[ makeUnique( 'some-alias' ) ]
		)
			.withHeader( 'If-Unmodified-Since', yesterday )
			.makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '415 - unsupported media type', async () => {
		const response = await newAddPropertyAliasesInLanguageRequestBuilder(
			existingPropertyId,
			'en',
			[ makeUnique( 'some-alias' ) ]
		)
			.withHeader( 'Content-Type', 'text/plain' )
			.makeRequest();

		expect( response ).to.have.status( 415 );
		expect( response ).to.satisfyApiSpec;
	} );
} );
