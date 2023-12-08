'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createUniqueStringProperty,
	newLegacyStatementWithRandomStringValue,
	changeEntityProtectionStatus,
	createEntity,
	editEntity
} = require( '../helpers/entityHelper' );
const { requireExtensions } = require( '../../../../../tests/api-testing/utils' );
const {
	editRequestsOnItem,
	editRequestsOnProperty,
	getRequestsOnItem,
	getRequestsOnProperty
} = require( '../helpers/happyPathRequestBuilders' );
const rbf = require( '../helpers/RequestBuilderFactory' );

async function resetEntityTestData( id, statementPropertyId ) {
	return ( await editEntity( id, {
		labels: [ { language: 'en', value: `entity-with-statements-${utils.uniq()}` } ],
		descriptions: [ { language: 'en', value: `entity-with-statements-${utils.uniq()}` } ],
		aliases: [ { language: 'en', value: 'entity' }, { language: 'en', value: 'thing' } ],
		claims: [ newLegacyStatementWithRandomStringValue( statementPropertyId ) ]
	} ) ).entity;
}

describe( 'Auth', () => {

	const itemRequestInputs = {};
	const propertyRequestInputs = {};
	let user;

	before( async () => {
		const statementPropertyId = ( await createUniqueStringProperty() ).entity.id;

		const itemId = ( await createEntity( 'item', {} ) ).entity.id;
		const itemData = await resetEntityTestData( itemId, statementPropertyId );
		itemRequestInputs.mainTestSubject = itemId;
		itemRequestInputs.itemId = itemId;
		itemRequestInputs.statementId = itemData.claims[ statementPropertyId ][ 0 ].id;
		itemRequestInputs.statementPropertyId = statementPropertyId;

		const propertyId = ( await createUniqueStringProperty() ).entity.id;
		const propertyData = await resetEntityTestData( propertyId, statementPropertyId );
		propertyRequestInputs.mainTestSubject = propertyId;
		propertyRequestInputs.propertyId = propertyId;
		propertyRequestInputs.statementId = propertyData.claims[ statementPropertyId ][ 0 ].id;
		propertyRequestInputs.statementPropertyId = statementPropertyId;

		user = await action.mindy();
	} );

	const useRequestInputs = ( requestInputs ) => ( newReqBuilder ) => ( {
		newRequestBuilder: () => newReqBuilder( requestInputs ),
		requestInputs
	} );

	const editRequestsWithInputs = [
		...editRequestsOnItem.map( useRequestInputs( itemRequestInputs ) ),
		...editRequestsOnProperty.map( useRequestInputs( propertyRequestInputs ) )
	];

	[
		...editRequestsWithInputs,
		...getRequestsOnItem.map( useRequestInputs( itemRequestInputs ) ),
		...getRequestsOnProperty.map( useRequestInputs( propertyRequestInputs ) )
	].forEach( ( { newRequestBuilder, requestInputs } ) => {
		describe( `Authentication - ${newRequestBuilder().getRouteDescription()}`, () => {

			afterEach( async () => {
				if ( newRequestBuilder().getMethod() === 'DELETE' ) {
					const entityData = await resetEntityTestData(
						requestInputs.mainTestSubject,
						requestInputs.statementPropertyId
					);
					requestInputs.statementId = entityData.claims[ requestInputs.statementPropertyId ][ 0 ].id;
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

	const authTestRequests = [
		{
			newRequestBuilder: () => rbf.newRemovePropertyLabelRequestBuilder( propertyRequestInputs.propertyId, 'en' ),
			requestInputs: propertyRequestInputs
		},
		{
			newRequestBuilder: () => rbf.newAddPropertyAliasesInLanguageRequestBuilder(
				propertyRequestInputs.propertyId,
				'en',
				[ 'my property alias' ]
			),
			requestInputs: propertyRequestInputs
		},
		...editRequestsWithInputs
	];

	describe( 'Authorization', () => {
		function assertPermissionDenied( response ) {
			expect( response ).to.have.status( 403 );
			assert.strictEqual( response.body.httpCode, 403 );
			assert.strictEqual( response.body.httpReason, 'Forbidden' );
			assert.strictEqual( response.body.error, 'rest-write-denied' );
		}

		editRequestsWithInputs.forEach( ( { newRequestBuilder } ) => {
			it( `Unauthorized bot edit - ${newRequestBuilder().getRouteDescription()}`, async () => {
				assertPermissionDenied(
					await newRequestBuilder().withJsonBodyParam( 'bot', true ).makeRequest()
				);
			} );
		} );

		authTestRequests.forEach( ( { newRequestBuilder } ) => {
			describe( `Blocked user - ${newRequestBuilder().getRouteDescription()}`, () => {
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

				it( 'cannot edit if blocked', async () => {
					const response = await newRequestBuilder().withUser( user ).makeRequest();
					expect( response ).to.have.status( 403 );
				} );
			} );
		} );

		// protecting/unprotecting does not always take effect immediately. These tests are isolated here to avoid
		// accidentally testing against a protected page in the other tests and receiving false positive results.
		authTestRequests.forEach( ( { newRequestBuilder, requestInputs } ) => {
			describe( `Protected entity page - ${newRequestBuilder().getRouteDescription()}`, () => {
				before( async () => {
					await changeEntityProtectionStatus( requestInputs.mainTestSubject, 'sysop' ); // protect
				} );

				after( async () => {
					await changeEntityProtectionStatus( requestInputs.mainTestSubject, 'all' ); // unprotect
				} );

				it( `Permission denied - ${newRequestBuilder().getRouteDescription()}`, async function () {
					// this test often hits a race condition where this request is made before the entity is protected
					this.retries( 3 );

					assertPermissionDenied( await newRequestBuilder().makeRequest() );
				} );
			} );
		} );
	} );
} );
