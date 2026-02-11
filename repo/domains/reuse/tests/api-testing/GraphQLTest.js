'use strict';

const { assert, clientFactory } = require( 'api-testing' );
const config = require( 'api-testing/lib/config' );
const { RequestBuilder } = require( '../../../../rest-api/tests/mocha/helpers/RequestBuilder.js' );

async function createItem( item ) {
	return ( await new RequestBuilder()
		.withRoute( 'POST', '/v1/entities/items' )
		.withJsonBodyParam( 'item', item )
		.makeRequest() ).body;
}

function queryGraphQL( query ) {
	return clientFactory.getHttpClient()
		.post( config.base_uri + 'api.php?action=wbgraphql&format=json' )
		.type( 'json' )
		.send( { query } );
}

describe( 'Wikibase GraphQL', () => {
	let item;
	before( async () => {
		item = await createItem( {} );
	} );

	it( 'can get labels of linked entities', async () => {
		const response = await queryGraphQL( `{
			item(id: "${ item.id }") { id }
		}` );

		// TODO actually test labels of linked entities
		assert.deepEqual(
			response.body,
			{
				data: {
					item: { id: item.id }
				}
			}
		);
	} );
} );
