'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newGetItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

async function createItemWithAllFields() {
	const statementPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
	const itemId = ( await entityHelper.createEntity( 'item', {
		labels: { en: { language: 'en', value: `non-empty-item-${utils.uniq()}` } },
		descriptions: { en: { language: 'en', value: 'non-empty-item-description' } },
		aliases: { en: [ { language: 'en', value: 'non-empty-item-alias' } ] },
		claims: [
			{ // with value, without qualifiers or references
				mainsnak: {
					snaktype: 'value',
					property: statementPropertyId,
					datavalue: { value: 'im a statement value', type: 'string' }
				}, type: 'statement', rank: 'normal'
			},
			{ // no value, with qualifier and reference
				mainsnak: {
					snaktype: 'novalue',
					property: statementPropertyId
				},
				type: 'statement',
				rank: 'normal',
				qualifiers: [
					{
						snaktype: 'value',
						property: statementPropertyId,
						datavalue: { value: 'im a qualifier value', type: 'string' }
					}
				],
				references: [ {
					snaks: [ {
						snaktype: 'value',
						property: statementPropertyId,
						datavalue: { value: 'im a reference value', type: 'string' }
					} ]
				} ]
			}
		]
	} ) ).entity.id;

	await entityHelper.createLocalSitelink( itemId, utils.title( 'Sitelink Test' ) );

	return itemId;
}

describe( newGetItemRequestBuilder().getRouteDescription(), () => {

	let itemId;
	let latestRevisionId;

	before( async () => {
		const createItemResponse = await entityHelper.createEntity( 'item', {} );
		itemId = createItemResponse.entity.id;
		latestRevisionId = createItemResponse.entity.lastrevid;
	} );

	it( '200 OK response is valid for a non-empty item', async () => {
		const id = await createItemWithAllFields();
		const response = await newGetItemRequestBuilder( id ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await entityHelper.createRedirectForItem( itemId );

		const response = await newGetItemRequestBuilder( redirectSourceId ).makeRequest();

		expect( response ).to.have.status( 308 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetItemRequestBuilder( itemId )
			.withHeader( 'If-None-Match', `"${latestRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetItemRequestBuilder( 'X123' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetItemRequestBuilder( 'Q99999' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
