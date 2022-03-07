'use strict';

const { REST, assert } = require( 'api-testing' );
const SwaggerParser = require( '@apidevtools/swagger-parser' );
const OpenAPIResponseValidator = require( 'openapi-response-validator' ).default;
const createEntity = require( '../helpers/createEntity' );

describe( 'GET /entities/items/{id} ', () => {
	let testItemId;
	const basePath = 'rest.php/wikibase/v0';

	before( async () => {
		const response = await createEntity( 'item', {} );
		testItemId = response.entity.id;
	} );

	it( 'is valid for an "empty" item', async () => {
		const spec = await SwaggerParser.dereference( './specs/openapi.json' );

		const rest = new REST( basePath );
		const response = await rest.get( `/entities/items/${testItemId}` );
		const responseValidator = new OpenAPIResponseValidator(
			spec.paths[ '/entities/items/{entity_id}' ].get
		);

		const errors = responseValidator.validateResponse(
			200,
			response.body
		);

		assert.ok( errors === undefined );
	} );
} );
