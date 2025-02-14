'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { describeWithTestData } = require( '../helpers/describeWithTestData' );
const { newCreateItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const {
	getItemEditRequests,
	getPropertyEditRequests,
	getItemCreateRequest
} = require( '../helpers/happyPathRequestBuilders' );

describe( 'Too Many Requests', () => {
	before( async () => {
		// ensure one temp user is created
		await newCreateItemRequestBuilder( { labels: { en: `en-label-${utils.uniq()}` } } )
			.withConfigOverride( 'wgAutoCreateTempUser', { enabled: true } )
			.withConfigOverride( 'wgTempAccountCreationThrottle', [ { count: 1, seconds: 86400 } ] )
			.makeRequest();
	} );

	describeWithTestData( 'Temp User Creation Limit', ( itemRequestInputs, propertyRequestInputs ) => {
		[
			...getItemEditRequests( itemRequestInputs ),
			...getPropertyEditRequests( propertyRequestInputs ),
			getItemCreateRequest( itemRequestInputs )
		].forEach( ( { newRequestBuilder } ) => {
			it( `${newRequestBuilder().getRouteDescription()} responds with a valid 429 response`, async () => {
				const response = await newRequestBuilder()
					// -1 means CACHE_ANYTHING. This is needed because the throttler relies on the cache.
					.withConfigOverride( 'wgMainCacheType', -1 )
					.withConfigOverride( 'wgAutoCreateTempUser', { enabled: true } )
					.withConfigOverride( 'wgTempAccountCreationThrottle', [ { count: 1, seconds: 86400 } ] )
					.makeRequest();

				expect( response ).to.have.status( 429 );
				expect( response ).to.satisfyApiSchema;
			} );
		} );
	} );
} );
