'use strict';

const assert = require( 'assert' );
const happyPathBuilders = require( '../helpers/happyPathRequestBuilders' );
const extensionRepoConfig = require( '../../../../../../extension-repo.json' );

describe( 'Route Coverage Tests', () => {
	const EXCLUDED_ROUTES = [
		'GET /v1/openapi.json',
		'GET /v1/property-data-types'
	];

	const mockInputs = {
		itemId: 'Q123',
		propertyId: 'P123',
		statementId: 'Q123$some-guid',
		statementPropertyId: 'P123',
		siteId: 'enwiki',
		linkedArticle: 'Test Article'
	};

	function routeToString( route ) {
		return `${route.method} ${route.path}`;
	}

	function getAllProductionRoutes() {
		return extensionRepoConfig.RestRoutes.map(
			( route ) => ( {
				method: route.method,
				path: route.path.split( '/wikibase' )[ 1 ]
			} )
		);
	}

	function getAllHappyPathRoutes() {
		return [
			...happyPathBuilders.getItemGetRequests( mockInputs ),
			...happyPathBuilders.getPropertyGetRequests( mockInputs ),
			...happyPathBuilders.getPropertyEditRequests( mockInputs ),
			...happyPathBuilders.getItemEditRequests( mockInputs ),
			happyPathBuilders.getItemCreateRequest( mockInputs ),
			happyPathBuilders.getPropertyCreateRequest( mockInputs )
		].map( ( { newRequestBuilder } ) => {
			const builder = newRequestBuilder();
			return {
				method: builder.method,
				path: builder.route
			};
		} );
	}

	it( 'should have all production routes covered in happy path builders', () => {
		const productionRoutes = getAllProductionRoutes()
			.map( ( route ) => routeToString( route ) )
			.filter( ( route ) => !EXCLUDED_ROUTES.includes( route ) );

		const happyPathRoutes = getAllHappyPathRoutes()
			.map( ( route ) => routeToString( route ) );

		const missingRoutes = productionRoutes.filter(
			( route ) => !happyPathRoutes.includes( route )
		);

		if ( missingRoutes.length > 0 ) {
			assert.fail(
				`Found ${missingRoutes.length} production routes not covered in happy path builders:\n\n` +
				missingRoutes.join( '\n' )
			);
		}
	} );
} );
