'use strict';

const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const {
	createRedirectForItem,
	createItemWithStatements,
	newStatementWithRandomStringValue,
	getLatestEditMetadata,
	getStringPropertyId,
	getItemId
} = require( '../helpers/entityHelper' );
const { newGetItemStatementsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetItemStatementsRequestBuilder().getRouteDescription(), () => {

	let itemId;
	let lastRevisionId;

	before( async () => {
		itemId = await getItemId();
		lastRevisionId = ( await getLatestEditMetadata( itemId ) ).revid;
	} );

	it( '200 OK response is valid for an Item with statements', async () => {
		const statementPropertyId = await getStringPropertyId();
		const { id } = await createItemWithStatements( [
			newStatementWithRandomStringValue( statementPropertyId ),
			newStatementWithRandomStringValue( statementPropertyId )
		] );
		const response = await newGetItemStatementsRequestBuilder( id ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetItemStatementsRequestBuilder( itemId )
			.withHeader( 'If-None-Match', `"${lastRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await createRedirectForItem( itemId );

		const response = await newGetItemStatementsRequestBuilder( redirectSourceId ).makeRequest();

		expect( response ).to.have.status( 308 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetItemStatementsRequestBuilder( 'X123' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetItemStatementsRequestBuilder( 'Q99999' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

} );
