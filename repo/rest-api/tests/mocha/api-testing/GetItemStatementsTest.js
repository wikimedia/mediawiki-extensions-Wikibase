'use strict';

const { assert } = require( 'api-testing' );
const {
	createEntity,
	createRedirectForItem,
	createUniqueStringProperty,
	getLatestEditMetadata,
	newLegacyStatementWithRandomStringValue,
	createItemWithStatements
} = require( '../helpers/entityHelper' );
const { newGetItemStatementsRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( 'GET /entities/items/{id}/statements', () => {

	function makeEtag( ...revisionIds ) {
		return revisionIds.map( ( revId ) => `"${revId}"` ).join( ',' );
	}

	let testItemId;
	let testPropertyId;
	let testModified;
	let testRevisionId;
	let testStatements;

	before( async () => {
		testPropertyId = ( await createUniqueStringProperty() ).entity.id;

		testStatements = [
			newLegacyStatementWithRandomStringValue( testPropertyId ),
			newLegacyStatementWithRandomStringValue( testPropertyId )
		];
		const createItemResponse = await createItemWithStatements( testStatements );
		testItemId = createItemResponse.entity.id;

		const testItemCreationMetadata = await getLatestEditMetadata( testItemId );
		testModified = testItemCreationMetadata.timestamp;
		testRevisionId = testItemCreationMetadata.revid;
	} );

	it( 'can GET statements of an item with metadata', async () => {
		const response = await newGetItemStatementsRequestBuilder( testItemId )
			.assertValidRequest()
			.makeRequest();

		assert.equal( response.status, 200 );
		assert.exists( response.body[ testPropertyId ] );
		assert.equal(
			response.body[ testPropertyId ][ 0 ].value.content,
			testStatements[ 0 ].mainsnak.datavalue.value
		);
		assert.equal(
			response.body[ testPropertyId ][ 1 ].value.content,
			testStatements[ 1 ].mainsnak.datavalue.value
		);
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, makeEtag( testRevisionId ) );
	} );

	it( 'can GET empty statements list', async () => {
		const createItemResponse = await createEntity( 'item',
			{ labels: { en: { language: 'en', value: 'item without statements' } } }
		);
		const response = await newGetItemStatementsRequestBuilder( createItemResponse.entity.id )
			.assertValidRequest()
			.makeRequest();

		assert.equal( response.status, 200 );
		assert.empty( response.body );
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const itemId = 'X123';
		const response = await newGetItemStatementsRequestBuilder( itemId )
			.assertInvalidRequest()
			.makeRequest();

		assert.equal( response.status, 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'invalid-item-id' );
		assert.include( response.body.message, itemId );
	} );

	it( '404 error - item not found', async () => {
		const itemId = 'Q999999';
		const response = await newGetItemStatementsRequestBuilder( itemId )
			.assertValidRequest()
			.makeRequest();

		assert.equal( response.status, 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'item-not-found' );
		assert.include( response.body.message, itemId );
	} );

	it( '308 - item redirected', async () => {
		const redirectTarget = testItemId;
		const redirectSource = await createRedirectForItem( redirectTarget );

		const response = await newGetItemStatementsRequestBuilder( redirectSource )
			.assertValidRequest()
			.makeRequest();

		assert.equal( response.status, 308 );

		assert.isTrue(
			new URL( response.headers.location ).pathname
				.endsWith( `rest.php/wikibase/v0/entities/items/${redirectTarget}/statements` )
		);
	} );

} );
