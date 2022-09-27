'use strict';

const chai = require( 'chai' );
const entityHelper = require( '../helpers/entityHelper' );
const { action, utils } = require( 'api-testing' );
const { newGetItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const expect = chai.expect;

async function createItemWithAllFields() {
	const stringPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
	const siteId = ( await action.getAnon().meta(
		'wikibase',
		{ wbprop: 'siteid' }
	) ).siteid;
	const pageWithSiteLink = utils.title( 'SiteLink Test' );
	await action.getAnon().edit( pageWithSiteLink, { text: 'sitelink test' } );

	return entityHelper.createEntity( 'item', {
		labels: { en: { language: 'en', value: `non-empty-item-${utils.uniq()}` } },
		descriptions: { en: { language: 'en', value: 'non-empty-item-description' } },
		aliases: { en: [ { language: 'en', value: 'non-empty-item-alias' } ] },
		sitelinks: {
			[ siteId ]: {
				site: siteId,
				title: pageWithSiteLink
			}
		},
		claims: [
			{ // with value, without qualifiers or references
				mainsnak: {
					snaktype: 'value',
					property: stringPropertyId,
					datavalue: { value: 'im a statement value', type: 'string' }
				}, type: 'statement', rank: 'normal'
			},
			{ // no value, with qualifier and reference
				mainsnak: {
					snaktype: 'novalue',
					property: stringPropertyId
				},
				type: 'statement',
				rank: 'normal',
				qualifiers: [
					{
						snaktype: 'value',
						property: stringPropertyId,
						datavalue: { value: 'im a qualifier value', type: 'string' }
					}
				],
				references: [ {
					snaks: [ {
						snaktype: 'value',
						property: stringPropertyId,
						datavalue: { value: 'im a reference value', type: 'string' }
					} ]
				} ]
			}
		]
	} );
}

describe( 'validate GET /entities/items/{id} responses against OpenAPI document', () => {

	let itemId;
	let latestRevisionId;

	before( async () => {
		const createItemResponse = await entityHelper.createEntity( 'item', {} );
		itemId = createItemResponse.entity.id;
		latestRevisionId = createItemResponse.entity.lastrevid;
	} );

	it( '200 OK response is valid for an "empty" item', async () => {
		const response = await newGetItemRequestBuilder( itemId ).makeRequest();

		expect( response.status ).to.equal( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '200 OK response is valid for a non-empty item', async () => {
		const { entity: { id } } = await createItemWithAllFields();
		const response = await newGetItemRequestBuilder( id ).makeRequest();

		expect( response.status ).to.equal( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await entityHelper.createRedirectForItem( itemId );

		const response = await newGetItemRequestBuilder( redirectSourceId ).makeRequest();

		expect( response.status ).to.equal( 308 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetItemRequestBuilder( itemId )
			.withHeader( 'If-None-Match', `"${latestRevisionId}"` )
			.makeRequest();

		expect( response.status ).to.equal( 304 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetItemRequestBuilder( 'X123' ).makeRequest();

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 Bad Request response is valid for an invalid field', async () => {
		const response = await newGetItemRequestBuilder( 'Q123' )
			.withQueryParam( '_fields', 'unknown_field' )
			.makeRequest();

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetItemRequestBuilder( 'Q99999' ).makeRequest();

		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
