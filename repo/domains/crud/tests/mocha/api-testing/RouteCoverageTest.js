'use strict';

const assert = require( 'assert' );
const happyPathBuilders = require( '../helpers/happyPathRequestBuilders' );
const extensionRepoConfig = require( '../../../../../../extension-repo.json' );

describe( 'Route Coverage Tests', () => {
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
		).filter(
			( route ) => route.path.startsWith( '/v1/entities' ) || route.path.startsWith( '/v1/statements' )
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
		const productionRoutes = getAllProductionRoutes().map( routeToString );
		const happyPathRoutes = getAllHappyPathRoutes().map( routeToString );

		assert.ok( productionRoutes.length > 0, 'No production routes found.' );
		assert.ok( happyPathRoutes.length > 0, 'No happy path routes found.' );

		const missingRoutes = productionRoutes.filter(
			( route ) => !happyPathRoutes.includes( route )
		);

		assert.ok(
			!missingRoutes.length > 0,
			`Found ${missingRoutes.length} production routes not covered in happy path builders:\n\n` +
			missingRoutes.join( '\n' )
		);
	} );
} );
