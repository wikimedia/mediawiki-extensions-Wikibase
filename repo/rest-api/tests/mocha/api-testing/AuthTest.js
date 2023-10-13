'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const rbf = require( '../helpers/RequestBuilderFactory' );
const {
	createUniqueStringProperty,
	newStatementWithRandomStringValue,
	newLegacyStatementWithRandomStringValue,
	changeEntityProtectionStatus,
	createEntity
} = require( '../helpers/entityHelper' );
const { requireExtensions } = require( '../../../../../tests/api-testing/utils' );
const {
	editRequestsOnItem,
	editRequestsOnProperty,
	getRequestsOnItem,
	getRequestsOnProperty
} = require( '../helpers/happyPathRequestBuilders' );
const { newSetPropertyDescriptionRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( 'Auth', () => {

	const itemRequestInputs = {};
	const propertyRequestInputs = {};

	let user;

	before( async () => {
		const statementPropertyId = ( await createUniqueStringProperty() ).entity.id;

		const entityParts = {
			claims: [ newLegacyStatementWithRandomStringValue( statementPropertyId ) ],
			descriptions: { en: { language: 'en', value: `entity-with-statements-${utils.uniq()}` } },
			labels: { en: { language: 'en', value: `entity-with-statements-${utils.uniq()}` } },
			aliases: {
				en: [ { language: 'en', value: 'entity' }, { language: 'en', value: 'thing' } ]
			}
		};

		const createItemResponse = await createEntity( 'item', entityParts );
		itemRequestInputs.itemId = createItemResponse.entity.id;
		itemRequestInputs.mainTestSubject = itemRequestInputs.itemId;
		itemRequestInputs.statementId = createItemResponse.entity.claims[ statementPropertyId ][ 0 ].id;
		itemRequestInputs.statementPropertyId = statementPropertyId;

		entityParts.datatype = 'string';
		const createPropertyResponse = await createEntity( 'property', entityParts );
		propertyRequestInputs.propertyId = createPropertyResponse.entity.id;
		propertyRequestInputs.mainTestSubject = propertyRequestInputs.propertyId;
		propertyRequestInputs.statementId = createPropertyResponse.entity.claims[ statementPropertyId ][ 0 ].id;
		propertyRequestInputs.statementPropertyId = statementPropertyId;

		user = await action.mindy();
	} );

	const useRequestInputs = ( requestInputs ) => ( newReqBuilder ) => ( {
		newRequestBuilder: () => newReqBuilder( requestInputs ),
		requestInputs
	} );

	const editRequestsWithInputs = [
		...editRequestsOnItem.map( useRequestInputs( itemRequestInputs ) ),
		...editRequestsOnProperty.map( useRequestInputs( propertyRequestInputs ) ),
		useRequestInputs( propertyRequestInputs )( ( { propertyId } ) =>
			newSetPropertyDescriptionRequestBuilder( propertyId, 'en', 'random-description-' + utils.uniq() )
		)
	];

	[
		...editRequestsWithInputs,
		...getRequestsOnItem.map( useRequestInputs( itemRequestInputs ) ),
		...getRequestsOnProperty.map( useRequestInputs( propertyRequestInputs ) )
	].forEach( ( { newRequestBuilder, requestInputs } ) => {
		describe( `Authentication - ${newRequestBuilder().getRouteDescription()}`, () => {

			afterEach( async () => {
				if ( newRequestBuilder().getMethod() === 'DELETE' ) {
					const addStatementRequestBuilder = requestInputs.mainTestSubject === requestInputs.itemId ?
						rbf.newAddItemStatementRequestBuilder :
						rbf.newAddPropertyStatementRequestBuilder;
					requestInputs.statementId = ( await addStatementRequestBuilder(
						requestInputs.mainTestSubject,
						newStatementWithRandomStringValue( requestInputs.statementPropertyId )
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

		editRequestsWithInputs.forEach( ( { newRequestBuilder } ) => {
			it( `Unauthorized bot edit - ${newRequestBuilder().getRouteDescription()}`, async () => {
				assertPermissionDenied(
					await newRequestBuilder()
						.withJsonBodyParam( 'bot', true )
						.makeRequest()
				);
			} );
		} );

		editRequestsWithInputs.forEach( ( { newRequestBuilder, requestInputs } ) => {
			describe( 'Protected entity page', () => {
				before( async () => {
					await changeEntityProtectionStatus( requestInputs.mainTestSubject, 'sysop' ); // protect
				} );

				after( async () => {
					await changeEntityProtectionStatus( requestInputs.mainTestSubject, 'all' ); // unprotect
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

				it( 'cannot edit if blocked', async () => {
					const response = await newRequestBuilder().withUser( user ).makeRequest();
					expect( response ).to.have.status( 403 );
				} );
			} );
		} );
	} );
} );
