'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createRedirectForItem, getLatestEditMetadata } = require( '../helpers/entityHelper' );
const {
	newGetItemAliasesInLanguageRequestBuilder,
	newCreateItemRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( newGetItemAliasesInLanguageRequestBuilder().getRouteDescription(), () => {

	let itemId;
	let lastRevisionId;
	const languageCode = 'en';

	before( async () => {
		const createItemResponse = await newCreateItemRequestBuilder( {
			aliases: { en: [ 'an-English-alias-' + utils.uniq(), 'another-English-alias-' + utils.uniq() ] }
		} ).makeRequest();
		itemId = createItemResponse.body.id;
		lastRevisionId = ( await getLatestEditMetadata( itemId ) ).revid;
	} );

	it( '200 OK response is valid', async () => {
		const response = await newGetItemAliasesInLanguageRequestBuilder( itemId, languageCode ).makeRequest();
		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetItemAliasesInLanguageRequestBuilder( itemId, languageCode )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();
		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await createRedirectForItem( itemId );
		const response = await newGetItemAliasesInLanguageRequestBuilder( redirectSourceId, languageCode )
			.makeRequest();
		expect( response ).to.have.status( 308 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetItemAliasesInLanguageRequestBuilder( 'X123', languageCode ).makeRequest();
		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetItemAliasesInLanguageRequestBuilder( 'Q99999', languageCode ).makeRequest();
		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

} );
