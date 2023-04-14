'use strict';

const { assert, utils, action } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createEntity,
	createRedirectForItem,
	getLatestEditMetadata,
	newLegacyStatementWithRandomStringValue,
	createUniqueStringProperty
} = require( '../helpers/entityHelper' );
const { newGetItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );

const germanLabel = 'a-German-label-' + utils.uniq();
const englishLabel = 'an-English-label-' + utils.uniq();
const englishDescription = 'an-English-description-' + utils.uniq();

describe( 'GET /entities/items/{id}', () => {
	let testItemId;
	let testModified;
	let testRevisionId;
	let siteId;
	let testPropertyId;
	let testStatement;
	const linkedArticle = utils.title( 'Article-linked-to-test-item' );

	function newValidRequestBuilderWithTestItem() {
		return newGetItemRequestBuilder( testItemId ).assertValidRequest();
	}

	before( async () => {
		siteId = ( await action.getAnon().meta(
			'wikibase',
			{ wbprop: 'siteid' }
		) ).siteid;
		await action.getAnon().edit( linkedArticle, { text: 'sitelink test' } );

		testPropertyId = ( await createUniqueStringProperty() ).entity.id;
		testStatement = newLegacyStatementWithRandomStringValue( testPropertyId );

		const createItemResponse = await createEntity( 'item', {
			labels: {
				de: { language: 'de', value: germanLabel },
				en: { language: 'en', value: englishLabel }
			},
			descriptions: {
				en: { language: 'en', value: englishDescription }
			},
			sitelinks: {
				[ siteId ]: {
					site: siteId,
					title: linkedArticle
				}
			},
			claims: [ testStatement ]
		} );
		testItemId = createItemResponse.entity.id;

		const testItemCreationMetadata = await getLatestEditMetadata( testItemId );
		testModified = testItemCreationMetadata.timestamp;
		testRevisionId = testItemCreationMetadata.revid;
	} );

	it( 'can GET all item data including metadata', async () => {
		const response = await newValidRequestBuilderWithTestItem().makeRequest();

		expect( response ).to.have.status( 200 );

		assert.equal( response.body.id, testItemId );
		assert.deepEqual( response.body.aliases, {} ); // expect {}, not []
		assert.deepEqual( response.body.labels, {
			de: germanLabel,
			en: englishLabel
		} );
		assert.deepEqual( response.body.descriptions, { en: englishDescription } );

		assert.strictEqual(
			response.body.statements[ testPropertyId ][ 0 ].value.content,
			testStatement.mainsnak.datavalue.value
		);

		assert.include( response.body.sitelinks[ siteId ].url, linkedArticle );

		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, makeEtag( testRevisionId ) );
	} );

	it( 'can GET a partial item with single _fields param', async () => {
		const response = await newValidRequestBuilderWithTestItem()
			.withQueryParam( '_fields', 'labels' )
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual( response.body, {
			id: testItemId,
			labels: {
				de: germanLabel,
				en: englishLabel
			}
		} );
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, makeEtag( testRevisionId ) );
	} );

	it( 'can GET a partial item with multiple _fields params', async () => {
		const response = await newValidRequestBuilderWithTestItem()
			.withQueryParam( '_fields', 'labels,descriptions,aliases' )
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepEqual( response.body, {
			id: testItemId,
			labels: {
				de: germanLabel,
				en: englishLabel
			},
			descriptions: {
				en: englishDescription
			},
			aliases: {} // expect {}, not []
		} );
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, makeEtag( testRevisionId ) );
	} );

	it( '400 error - bad request, invalid item ID', async () => {
		const itemId = 'X123';
		const response = await newGetItemRequestBuilder( itemId )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'invalid-item-id' );
		assert.include( response.body.message, itemId );
	} );

	it( '400 error - bad request, invalid field', async () => {
		const itemId = 'Q123';
		const response = await newGetItemRequestBuilder( itemId )
			.withQueryParam( '_fields', 'unknown_field' )
			.assertInvalidRequest()
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'invalid-field' );
		assert.include( response.body.message, 'unknown_field' );
	} );

	it( '404 error - item not found', async () => {
		const itemId = 'Q999999';
		const response = await newGetItemRequestBuilder( itemId ).makeRequest();

		expect( response ).to.have.status( 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'item-not-found' );
		assert.include( response.body.message, itemId );
	} );

	describe( 'redirects', () => {
		let redirectSourceId;

		before( async () => {
			redirectSourceId = await createRedirectForItem( testItemId );
		} );

		it( 'responds with a 308 including the redirect target location', async () => {
			const response = await newGetItemRequestBuilder( redirectSourceId ).makeRequest();

			expect( response ).to.have.status( 308 );

			const redirectLocation = new URL( response.headers.location );
			assert.isTrue( redirectLocation.pathname.endsWith( `rest.php/wikibase/v0/entities/items/${testItemId}` ) );
			assert.empty( redirectLocation.search );
		} );

		it( 'keeps the original fields param in the Location header', async () => {
			const fields = 'labels,statements';
			const response = await newGetItemRequestBuilder( redirectSourceId )
				.withQueryParam( '_fields', fields )
				.makeRequest();

			expect( response ).to.have.status( 308 );

			const redirectLocation = new URL( response.headers.location );
			assert.equal(
				redirectLocation.searchParams.get( '_fields' ),
				fields
			);
		} );

	} );

} );
