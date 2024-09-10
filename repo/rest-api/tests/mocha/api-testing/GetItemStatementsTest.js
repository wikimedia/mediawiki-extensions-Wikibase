'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createEntity,
	createRedirectForItem,
	createUniqueStringProperty,
	getLatestEditMetadata,
	newStatementWithRandomStringValue,
	createItemWithStatements
} = require( '../helpers/entityHelper' );
const { newGetItemStatementsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newGetItemStatementsRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let testStatementPropertyId;
	let testStatementPropertyId2;
	let testModified;
	let testRevisionId;
	let testStatements;

	before( async () => {
		testStatementPropertyId = ( await createUniqueStringProperty() ).entity.id;
		testStatementPropertyId2 = ( await createUniqueStringProperty() ).entity.id;

		testStatements = [
			newStatementWithRandomStringValue( testStatementPropertyId ),
			newStatementWithRandomStringValue( testStatementPropertyId ),
			newStatementWithRandomStringValue( testStatementPropertyId2 )
		];
		testItemId = ( await createItemWithStatements( testStatements ) ).id;

		const testItemCreationMetadata = await getLatestEditMetadata( testItemId );
		testModified = testItemCreationMetadata.timestamp;
		testRevisionId = testItemCreationMetadata.revid;
	} );

	it( 'can GET statements of an item with metadata', async () => {
		const response = await newGetItemStatementsRequestBuilder( testItemId )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.exists( response.body[ testStatementPropertyId ] );
		assert.deepEqual(
			response.body[ testStatementPropertyId ][ 0 ].value,
			testStatements[ 0 ].value
		);
		assert.deepEqual(
			response.body[ testStatementPropertyId ][ 1 ].value,
			testStatements[ 1 ].value
		);
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, makeEtag( testRevisionId ) );
	} );

	it( 'can filter statements by property', async () => {
		const response = await newGetItemStatementsRequestBuilder( testItemId )
			.withQueryParam( 'property', testStatementPropertyId )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual( Object.keys( response.body ), [ testStatementPropertyId ] );
		assert.strictEqual( response.body[ testStatementPropertyId ].length, 2 );
	} );

	it( 'can GET empty statements list', async () => {
		const createItemResponse = await createEntity( 'item',
			{ labels: { en: { language: 'en', value: 'item without statements' } } }
		);
		const response = await newGetItemStatementsRequestBuilder( createItemResponse.entity.id )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.empty( response.body );
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const itemId = 'X123';
		const response = await newGetItemStatementsRequestBuilder( itemId )
			.assertInvalidRequest()
			.makeRequest();

		assertValidError(
			response,
			400,
			'invalid-path-parameter',
			{ parameter: 'item_id' }
		);
	} );

	it( '400 error - bad request, invalid property ID filter', async () => {
		const queryParamName = 'property';
		const response = await newGetItemStatementsRequestBuilder( testItemId )
			.withQueryParam( queryParamName, 'X123' )
			.assertInvalidRequest()
			.makeRequest();

		assertValidError( response, 400, 'invalid-query-parameter', { parameter: queryParamName } );
		assert.include( response.body.message, queryParamName );
	} );

	it( '404 error - item not found', async () => {
		const itemId = 'Q999999';
		const response = await newGetItemStatementsRequestBuilder( itemId )
			.assertValidRequest()
			.makeRequest();

		assertValidError( response, 404, 'item-not-found' );
		assert.include( response.body.message, itemId );
	} );

	it( '308 - item redirected', async () => {
		const redirectTarget = testItemId;
		const redirectSource = await createRedirectForItem( redirectTarget );

		const response = await newGetItemStatementsRequestBuilder( redirectSource )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 308 );
		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `rest.php/wikibase/v0/entities/items/${redirectTarget}/statements` )
		);
	} );

} );
