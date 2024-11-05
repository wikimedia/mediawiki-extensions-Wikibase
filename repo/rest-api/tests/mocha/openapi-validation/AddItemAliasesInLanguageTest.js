'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createRedirectForItem } = require( '../helpers/entityHelper' );
const {
	newAddItemAliasesInLanguageRequestBuilder,
	newCreateItemRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

function makeUnique( text ) {
	return `${text}-${utils.uniq()}`;
}

describe( newAddItemAliasesInLanguageRequestBuilder().getRouteDescription(), () => {

	let existingItemId;
	const languageWithExistingAlias = 'en';

	before( async () => {
		const createItemResponse = await newCreateItemRequestBuilder(
			{ aliases: { [ languageWithExistingAlias ]: [ makeUnique( 'en-alias' ) ] } }
		).makeRequest();
		existingItemId = createItemResponse.body.id;
	} );

	it( '200 - added alias to existing ones', async () => {
		const response = await newAddItemAliasesInLanguageRequestBuilder(
			existingItemId,
			'en',
			[ makeUnique( 'added-en-alias' ) ]
		).makeRequest();
		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '201 - added alias where there were no existing ones', async () => {
		const response = await newAddItemAliasesInLanguageRequestBuilder(
			existingItemId,
			'de',
			[ makeUnique( 'first-de-alias' ) ]
		).makeRequest();
		expect( response ).to.have.status( 201 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 - invalid alias', async () => {
		const response = await newAddItemAliasesInLanguageRequestBuilder(
			existingItemId,
			'en',
			[ 'tab character \t not allowed' ]
		).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 - item does not exist', async () => {
		const response = await newAddItemAliasesInLanguageRequestBuilder(
			'Q9999999',
			'en',
			[ makeUnique( 'some-alias' ) ]
		).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '409 - item redirected', async () => {
		const redirectSource = await createRedirectForItem( existingItemId );
		const response = await newAddItemAliasesInLanguageRequestBuilder(
			redirectSource,
			'en',
			[ makeUnique( 'some-alias' ) ]
		).makeRequest();

		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newAddItemAliasesInLanguageRequestBuilder(
			existingItemId,
			'en',
			[ makeUnique( 'some-alias' ) ]
		)
			.withHeader( 'If-Unmodified-Since', yesterday )
			.makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSchema;
	} );
} );
