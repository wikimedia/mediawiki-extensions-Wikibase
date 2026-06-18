'use strict';

const { action, assert, clientFactory, utils, wiki } = require( 'api-testing' );
const { expect } = require( 'chai' );
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

function queryGraphQL( requestBody ) {
	return clientFactory.getHttpClient()
		.post( config.base_uri + 'api.php?action=wbgraphql&format=json' )
		.set( 'X-Config-Override', JSON.stringify( {
			wgSearchType: 'CirrusSearch',
			wgShowExceptionDetails: false // this makes errors more verbose and is off in prod
		} ) )
		.type( 'json' )
		.send( requestBody );
}

describe( 'Wikibase GraphQL', () => {
	let item1;
	let item2;
	let item3;
	let property1;
	let property2;
	let property3;
	let property4;
	let siteId;
	const linkedArticle = utils.title( 'Article-linked-to-test-item' );
	const item1label = `vegetable ${ utils.uniq() }`;
	const item2label = `potato ${ utils.uniq() }`;
	const property1label = `isType ${ utils.uniq() }`;
	const property2label = `hasRelationship ${ utils.uniq() }`;
	const item2Property3StatementValue = 'sweet potato';
	const item1ExternalId = 'external-id';

	before( async () => {
		await action.getAnon().edit( linkedArticle, { text: 'sitelink test page' } );
		siteId = ( await action.getAnon().meta(
			'wikibase',
			{ wbprop: 'siteid' }
		) ).siteid;

		property1 = await createProperty( {
			data_type: 'wikibase-item',
			labels: { en: property1label }
		} );

		property2 = await createProperty( {
			data_type: 'wikibase-item',
			labels: { en: property2label }
		} );

		property3 = await createProperty( {
			data_type: 'string',
			labels: { en: `string property ${ utils.uniq() }` }
		} );

		property4 = await createProperty( {
			data_type: 'external-id',
			labels: { en: `external id property ${ utils.uniq() }` }
		} );

		// item with label "vegetable", statements: hasRelationship->somevalue, external-id
		item1 = await createItem( {
			labels: { en: item1label },
			statements: {
				[ property2.id ]: [
					{
						property: { id: property2.id },
						value: { type: 'somevalue' }
					}
				]
			},
			sitelinks: { [ siteId ]: { title: linkedArticle } }
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
				],
				[ property3.id ]: [
					{
						property: { id: property3.id },
						value: { type: 'value', content: item2Property3StatementValue }
					}
				]
			}
		} );

		item3 = await createItem( {
			statements: {
				[ property4.id ]: [
					{
						property: { id: property4.id },
						value: { type: 'value', content: item1ExternalId }
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
		const response = await queryGraphQL( { query: `
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
			}` } );

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
		const response = await queryGraphQL( { query: `
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
			}` } );

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

	describe( 'searchItems', () => {
		before( async function () {
			// Skip search tests in CI if OpenSearch is not available
			if ( process.env.QUIBBLE_OPENSEARCH && process.env.QUIBBLE_OPENSEARCH !== 'true' ) {
				this.skip();
			}
		} );

		it( 'property value pair match with "and"', async function () {
			const response = await queryGraphQL( { query: `
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
								statements(propertyId: "${ property1.id }") {
									value {
										... on ItemValue {
											id
										}
									}
								}
							}
						}
					}
				}` } );

			assert.deepEqual(
				response.body,
				{
					data: {
						searchItems: {
							edges: [
								{
									node: {
										id: item2.id,
										label: item2label,
										statements: [
											{
												value: {
													id: item1.id
												}
											}
										]
									}
								}
							]
						}
					}
				}
			);
		} );

		it( 'property value pair match when value contains a space', async function () {
			const response = await queryGraphQL( { query: `
				{
					searchItems(
						query: {
							property: "${ property3.id }", value: "${ item2Property3StatementValue }"
						}
					) {
						edges {
							node { id }
						}
					}
				}` } );

			assert.deepEqual(
				response.body,
				{
					data: {
						searchItems: {
							edges: [
								{ node: { id: item2.id } }
							]
						}
					}
				}
			);
		} );

		it( 'OR search match when value contains a space', async function () {
			const response = await queryGraphQL( { query: `
				{
					searchItems(
						query: {
							or: [
								{ property: "${ property1.id }", value: "${ item1.id }" },
								{ property: "${ property1.id }", value: "does not exist" },
							]
						}
					) {
						edges {
							node { id }
						}
					}
				}` } );

			assert.deepEqual(
				response.body,
				{
					data: {
						searchItems: {
							edges: [
								{ node: { id: item2.id } }
							]
						}
					}
				}
			);
		} );

		it( 'property value pair match with "or"', async function () {
			const response = await queryGraphQL( { query: `
				{
					searchItems(
						query: {
							or: [
								{ property: "${ property1.id }" }
								{ property: "${ property2.id }" }
							]
						}
					) {
						edges {
							node { id }
						}
					}
				}` } );

			// the order of search results is not guaranteed, so we just test that it contains the two expected elements
			// in any order
			const results = response.body.data.searchItems.edges;
			assert.lengthOf( results, 2 );
			expect( results ).to.deep.include( { node: { id: item1.id } } );
			expect( results ).to.deep.include( { node: { id: item2.id } } );
		} );
	} );

	it( 'property value pair match with "not"', async function () {
		const response = await queryGraphQL( { query: `
					{
						searchItems(
							query: {
								not:
									{ property: "${ property3.id }", value: "${ item2Property3StatementValue }" }
							}
						) {
							edges {
								node { id }
							}
						}
					}` } );

		const results = response.body.data.searchItems.edges;
		expect( results ).to.not.deep.include( { node: { id: item2.id } } );
	} );

	it( 'can look up items by sitelink', async () => {
		const sitelinkTitle = item1.sitelinks[ siteId ].title;
		const response = await queryGraphQL( { query: `
			{
				itemBySitelink(title: "${ sitelinkTitle }", siteId: "${ siteId }") { id }
			}` } );

		assert.deepEqual(
			response.body,
			{
				data: {
					itemBySitelink: { id: item1.id }
				}
			}
		);
	} );

	it( 'can look up items by externalId', async function () {
		if ( process.env.QUIBBLE_OPENSEARCH && process.env.QUIBBLE_OPENSEARCH !== 'true' ) {
			this.skip();
		}

		const response = await queryGraphQL( { query: `
			{
				itemByExternalId(property: "${ property4.id }", externalId: "${ item1ExternalId }") {
					... on Item { id }
				}
			}` } );

		assert.deepEqual(
			response.body,
			{
				data: {
					itemByExternalId: { id: item3.id }
				}
			}
		);
	} );

	it( 'supports introspection', async () => {
		const response = await queryGraphQL( { query: `
			{
				__schema {
					queryType {
						fields { name }
					}
				}
			}` } );

		assert.deepEqual(
			response.body,
			{
				data: {
					__schema: {
						queryType: {
							fields: [
								{ name: 'item' },
								{ name: 'itemsById' },
								{ name: 'itemByExternalId' },
								{ name: 'searchItems' },
								{ name: 'itemBySitelink' }
							]
						}
					}
				}
			}
		);
	} );

	it( 'retains boolean fields (T419560)', async () => {
		const response = await queryGraphQL( { query: `
		{
			__type(name: "Item") {
				fields { isDeprecated }
			}
		}` } );

		expect( response.body.data.__type.fields )
			.to.deep.include( { isDeprecated: false } );
	} );

	it( 'supports operationName parameter, required only if multiple operations are present in the query', async () => {
		const response = await queryGraphQL( {
			query: `query item1 { item(id: "${ item1.id }") { id } }
			        query item2 { item(id: "${ item2.id }"){ id } }`,
			operationName: 'item1'
		} );

		assert.deepEqual(
			response.body,
			{
				data: {
					item: {
						id: item1.id
					}
				}
			} );
	} );

	it( 'supports variables parameter', async () => {
		const response = await queryGraphQL( {
			query: 'query item($id: ItemId!) { item(id : $id) { id } }',
			variables: { id: item1.id }
		} );

		assert.deepEqual(
			response.body,
			{
				data: {
					item: {
						id: item1.id
					}
				}
			} );
	} );

	it( 'throws an error when query is missing', async () => {
		const response = await queryGraphQL( { query: '' } );

		assert.deepEqual(
			response.body,
			{ errors: [ { message: "The 'query' field is required and must not be empty" } ] }
		);
	} );

	it( 'rejects requests with unsupported content-type', async () => {
		const response = await clientFactory.getHttpClient()
			.post( config.base_uri + 'api.php?action=wbgraphql&format=json' )
			.type( 'form' )
			.send( { query: '' } );

		assert.deepEqual(
			response.body,
			{ errors: [ { message: "Requests must be sent as 'application/json'" } ] }
		);
	} );
} );
