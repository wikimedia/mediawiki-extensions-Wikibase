'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newGetItemRequestBuilder, newCreateItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

async function createItemWithAllFields() {
	const statementPropertyId = await entityHelper.getStringPropertyId();
	const itemId = ( await newCreateItemRequestBuilder( {
		labels: { en: `non-empty-item-${utils.uniq()}` },
		descriptions: { en: 'non-empty-item-description' },
		aliases: { en: [ 'non-empty-item-alias' ] },
		statements: {
			[ statementPropertyId ]: [
				{ // with value, without qualifiers or references
					property: { id: statementPropertyId },
					value: { type: 'value', content: 'im a statement value' },
					rank: 'normal'
				},
				{ // no value, with qualifier and reference
					property: { id: statementPropertyId },
					value: { type: 'novalue' },
					rank: 'normal',
					qualifiers: [
						{
							property: { id: statementPropertyId },
							value: { type: 'value', content: 'im a qualifier value' }
						}
					],
					references: [ {
						parts: [ {
							property: { id: statementPropertyId },
							value: { type: 'value', content: 'im a reference value' }
						} ]
					} ]
				}
			] }
	} ).makeRequest() ).body.id;

	await entityHelper.createLocalSitelink( itemId, utils.title( 'Sitelink Test' ) );

	return itemId;
}

describe( newGetItemRequestBuilder().getRouteDescription(), () => {

	let itemId;
	let latestRevisionId;

	before( async () => {
		const createItemResponse = await newCreateItemRequestBuilder( {} ).makeRequest();
		itemId = createItemResponse.body.id;
		latestRevisionId = ( await entityHelper.getLatestEditMetadata( itemId ) ).revid;
	} );

	it( '200 OK response is valid for a non-empty item', async () => {
		const id = await createItemWithAllFields();
		const response = await newGetItemRequestBuilder( id ).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '308 Permanent Redirect response is valid for a redirected item', async () => {
		const redirectSourceId = await entityHelper.createRedirectForItem( itemId );

		const response = await newGetItemRequestBuilder( redirectSourceId ).makeRequest();

		expect( response ).to.have.status( 308 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '304 Not Modified response is valid', async () => {
		const response = await newGetItemRequestBuilder( itemId )
			.withHeader( 'If-None-Match', `"${latestRevisionId}"` )
			.makeRequest();

		expect( response ).to.have.status( 304 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 Bad Request response is valid for an invalid item ID', async () => {
		const response = await newGetItemRequestBuilder( 'X123' ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 Not Found response is valid for a non-existing item', async () => {
		const response = await newGetItemRequestBuilder( 'Q99999' ).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

} );
