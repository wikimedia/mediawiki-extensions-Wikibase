'use strict';

const { assert, clientFactory, utils, wiki } = require( 'api-testing' );
const config = require( 'api-testing/lib/config' );
const { RequestBuilder } = require( '../../../../rest-api/tests/mocha/helpers/RequestBuilder.js' );

async function createItem( item ) {
	return ( await new RequestBuilder()
		.withRoute( 'POST', '/v1/entities/items' )
		.withJsonBodyParam( 'item', item )
		.makeRequest() ).body;
}

async function createProperty( property ) {
	return ( await new RequestBuilder()
		.withRoute( 'POST', '/v1/entities/properties' )
		.withJsonBodyParam( 'property', property )
		.makeRequest() ).body;
}

function queryGraphQL( query ) {
	return clientFactory.getHttpClient()
		.post( config.base_uri + 'api.php?action=wbgraphql&format=json' )
		.set( 'X-Config-Override', JSON.stringify( { wgSearchType: 'CirrusSearch' } ) )
		.type( 'json' )
		.send( { query } );
}

describe( 'Wikibase GraphQL', () => {
	let item1;
	let item2;
	let property1;
	let property2;
	const item1label = `vegetable ${ utils.uniq() }`;
	const item2label = `potato ${ utils.uniq() }`;
	const property1label = `isType ${ utils.uniq() }`;
	const property2label = `hasRelationship ${ utils.uniq() }`;

	before( async () => {

		item1 = await createItem( {
			labels: { en: item1label }
		} );

		property1 = await createProperty( {
			data_type: 'wikibase-item',
			labels: { en: property1label }
		} );

		property2 = await createProperty( {
			data_type: 'wikibase-item',
			labels: { en: property2label }
		} );

		// Create item with two statements, potato: isType -> vegetable, hasRelationship->vegetable
		item2 = await createItem( {
			labels: { en: item2label },
			statements: {
				[ property1.id ]: [
					{
						property: { id: property1.id },
						value: { type: 'value', content: item1.id }
					}
				],
				[ property2.id ]: [
					{
						property: { id: property2.id },
						value: { type: 'value', content: item1.id }
					}
				]
			}
		} );

		await wiki.runAllJobs();
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 ); // apparently the index update still needs a bit after runAllJobs()
		} );
	} );

	it( 'can get labels of linked entities with item', async () => {
		const response = await queryGraphQL( `
			{
				item(id: "${ item2.id }") {
					id
					label(languageCode: "en")
					statements(propertyId: "${ property1.id }") {
						value {
							... on ItemValue {
								id
								label(languageCode: "en")
							}
						}
					}
				}
			}` );

		assert.deepEqual(
			response.body,
			{
				data: {
					item: {
						id: item2.id,
						label: item2label,
						statements: [
							{
								value: {
									id: item1.id,
									label: item1label
								}
							}
						]
					}
				}
			} );
	} );

	it( 'can get labels of linked entities of multiple items with itemsById', async () => {
		const response = await queryGraphQL( `
			{
				itemsById(ids: ["${ item2.id }", "${ item1.id }"]) {
					id
					label(languageCode: "en")
					statements(propertyId: "${ property1.id }") {
						value {
							... on ItemValue {
								id
								label(languageCode: "en")
							}
						}
					}
				}
			}` );

		assert.deepEqual(
			response.body,
			{
				data: {
					itemsById: [
						{
							id: item2.id,
							label: item2label,
							statements: [
								{
									value: {
										id: item1.id,
										label: item1label
									}
								}
							]
						},
						{
							id: item1.id,
							label: item1label,
							statements: []
						}
					]
				}
			}
		);
	} );

	it( 'property value pair match with searchItems', async () => {
		const response = await queryGraphQL( `
			{
				searchItems(
					query: {
						and: [
							{ property: "${ property1.id }", value: "${ item1.id }" }
							{ property: "${ property2.id }", value: "${ item1.id }" }
						]
					}
				) {
					edges {
						node {
							id
							label(languageCode: "en")
						}
					}
				}
			}` );

		assert.deepEqual(
			response.body,
			{
				data: {
					searchItems: {
						edges: [
							{
								node: {
									id: item2.id,
									label: item2label
								}
							}
						]
					}
				}
			}
		);
	} );
} );
