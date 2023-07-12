'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const rbf = require( '../helpers/RequestBuilderFactory' );
const {
	createUniqueStringProperty,
	newStatementWithRandomStringValue,
	newLegacyStatementWithRandomStringValue,
	changeItemProtectionStatus,
	createEntity
} = require( '../helpers/entityHelper' );
const { requireExtensions } = require( '../../../../../tests/api-testing/utils' );
const {
	editRequestsOnItem,
	getRequestsOnItem,
	getRequestsOnProperty
} = require( '../helpers/happyPathRequestBuilders' );

describe( 'Auth', () => {

	const itemRequestInputs = {};
	const propertyRequestInputs = {};

	let user;

	before( async () => {
		const propertyId = ( await createUniqueStringProperty() ).entity.id;

		const entityParts = {
			claims: [ newLegacyStatementWithRandomStringValue( propertyId ) ],
			descriptions: { en: { language: 'en', value: `entity-with-statements-${utils.uniq()}` } },
			labels: { en: { language: 'en', value: `entity-with-statements-${utils.uniq()}` } },
			aliases: {
				en: [ { language: 'en', value: 'entity' }, { language: 'en', value: 'thing' } ]
			}
		};

		const createItemResponse = await createEntity( 'item', entityParts );
		itemRequestInputs.stringPropertyId = propertyId;
		itemRequestInputs.itemId = createItemResponse.entity.id;
		itemRequestInputs.statementId = createItemResponse.entity.claims[ propertyId ][ 0 ].id;

		entityParts.datatype = 'string';
		const createPropertyResponse = await createEntity( 'property', entityParts );
		propertyRequestInputs.stringPropertyId = createPropertyResponse.entity.id;
		propertyRequestInputs.statementId = createPropertyResponse.entity.claims[ propertyId ][ 0 ].id;

		user = await action.mindy();
	} );

	const useRequestInputs = ( requestInputs ) => ( newReqBuilder ) => () => newReqBuilder( requestInputs );

	[
		...getRequestsOnItem.map( useRequestInputs( itemRequestInputs ) ),
		...getRequestsOnProperty.map( useRequestInputs( propertyRequestInputs ) ),
		...editRequestsOnItem.map( useRequestInputs( itemRequestInputs ) )
	].forEach( ( newRequestBuilder ) => {
		describe( `Authentication - ${newRequestBuilder().getRouteDescription()}`, () => {

			afterEach( async () => {
				if ( newRequestBuilder().getMethod() === 'DELETE' ) {
					itemRequestInputs.statementId = ( await rbf.newAddItemStatementRequestBuilder(
						itemRequestInputs.itemId,
						newStatementWithRandomStringValue( itemRequestInputs.stringPropertyId )
					).makeRequest() ).body.id;
				}
			} );

			it( 'has an X-Authenticated-User header with the logged in user', async () => {
				const response = await newRequestBuilder().withUser( user ).makeRequest();

				expect( response ).status.to.be.within( 200, 299 );
				assert.header( response, 'X-Authenticated-User', user.username );
			} );

			// eslint-disable-next-line mocha/no-skipped-tests
			describe.skip( 'OAuth', () => { // Skipping due to apache auth header issues. See T305709
				before( requireExtensions( [ 'OAuth' ] ) );

				it( 'responds with an error given an invalid bearer token', async () => {
					const response = newRequestBuilder()
						.withHeader( 'Authorization', 'Bearer this-is-an-invalid-token' )
						.makeRequest();

					expect( response ).to.have.status( 403 );
				} );
			} );
		} );
	} );

	describe( 'Authorization', () => {
		function assertPermissionDenied( response ) {
			expect( response ).to.have.status( 403 );
			assert.strictEqual( response.body.httpCode, 403 );
			assert.strictEqual( response.body.httpReason, 'Forbidden' );
			assert.strictEqual( response.body.error, 'rest-write-denied' );
		}

		editRequestsOnItem.map( useRequestInputs( itemRequestInputs ) ).forEach( ( newRequestBuilder ) => {
			describe( 'Protected item', () => {
				before( async () => {
					await changeItemProtectionStatus( itemRequestInputs.itemId, 'sysop' ); // protect
				} );

				after( async () => {
					await changeItemProtectionStatus( itemRequestInputs.itemId, 'all' ); // unprotect
				} );

				it( `Permission denied - ${newRequestBuilder().getRouteDescription()}`, async () => {
					assertPermissionDenied( await newRequestBuilder().makeRequest() );
				} );
			} );

			describe( 'Blocked user', () => {
				before( async () => {
					await user.action( 'block', {
						user: user.username,
						reason: 'testing',
						token: await user.token()
					}, 'POST' );
				} );

				after( async () => {
					await user.action( 'unblock', {
						user: user.username,
						token: await user.token()
					}, 'POST' );
				} );

				it( 'can not edit if blocked', async () => {
					const response = await newRequestBuilder().withUser( user ).makeRequest();
					expect( response ).to.have.status( 403 );
				} );
			} );

			it( `Unauthorized bot edit - ${newRequestBuilder().getRouteDescription()}`, async () => {
				assertPermissionDenied(
					await newRequestBuilder()
						.withJsonBodyParam( 'bot', true )
						.makeRequest()
				);
			} );
		} );
	} );
} );
