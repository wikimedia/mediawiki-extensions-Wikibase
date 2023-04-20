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
const { editRequests, getRequests } = require( '../helpers/happyPathRequestBuilders' );

describe( 'Auth', () => {

	const requestInputs = {};
	let user;

	before( async () => {
		requestInputs.stringPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const createEntityResponse = await createEntity(
			'item',
			{
				claims: [ newLegacyStatementWithRandomStringValue( requestInputs.stringPropertyId ) ],
				descriptions: { en: { language: 'en', value: `item-with-statements-${utils.uniq()}` } },
				labels: { en: { language: 'en', value: `item-with-statements-${utils.uniq()}` } },
				aliases: {
					en: [ { language: 'en', value: 'Douglas Noël Adams' }, { language: 'en', value: 'DNA' } ]
				}
			}
		);
		requestInputs.itemId = createEntityResponse.entity.id;
		requestInputs.statementId = createEntityResponse.entity.claims[ requestInputs.stringPropertyId ][ 0 ].id;
		user = await action.mindy();
	} );

	const setDescriptionRequest = () => rbf.newSetItemDescriptionRequestBuilder(
		requestInputs.itemId,
		'en',
		'random-test-description-' + utils.uniq()
	);

	[
		...getRequests,
		...editRequests,

		// TODO: move into editRequests, once Authorization works
		setDescriptionRequest
	].forEach( ( newRequestBuilder ) => {
		describe( `Authentication - ${newRequestBuilder( requestInputs ).getRouteDescription()}`, () => {

			afterEach( async () => {
				if ( newRequestBuilder( requestInputs ).getMethod() === 'DELETE' ) {
					requestInputs.statementId = ( await rbf.newAddItemStatementRequestBuilder(
						requestInputs.itemId,
						newStatementWithRandomStringValue( requestInputs.stringPropertyId )
					).makeRequest() ).body.id;
				}
			} );

			it( 'has an X-Authenticated-User header with the logged in user', async () => {
				const response = await newRequestBuilder( requestInputs ).withUser( user ).makeRequest();

				expect( response ).status.to.be.within( 200, 299 );
				assert.header( response, 'X-Authenticated-User', user.username );
			} );

			// eslint-disable-next-line mocha/no-skipped-tests
			describe.skip( 'OAuth', () => { // Skipping due to apache auth header issues. See T305709
				before( requireExtensions( [ 'OAuth' ] ) );

				it( 'responds with an error given an invalid bearer token', async () => {
					const response = newRequestBuilder( requestInputs )
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

		editRequests.forEach( ( newRequestBuilder ) => {
			describe( 'Protected item', () => {
				before( async () => {
					await changeItemProtectionStatus( requestInputs.itemId, 'sysop' ); // protect
				} );

				after( async () => {
					await changeItemProtectionStatus( requestInputs.itemId, 'all' ); // unprotect
				} );

				it( `Permission denied - ${newRequestBuilder( requestInputs ).getRouteDescription()}`, async () => {
					assertPermissionDenied( await newRequestBuilder( requestInputs ).makeRequest() );
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
					const response = await newRequestBuilder( requestInputs ).withUser( user ).makeRequest();
					expect( response ).to.have.status( 403 );
				} );
			} );

			it( `Unauthorized bot edit - ${newRequestBuilder( requestInputs ).getRouteDescription()}`, async () => {
				assertPermissionDenied(
					await newRequestBuilder( requestInputs )
						.withJsonBodyParam( 'bot', true )
						.makeRequest()
				);
			} );
		} );

		// TODO remove, once Authorization works
		it( 'Unauthorized bot edit - PUT /entities/items/{item_id}/descriptions/{language_code}', async () => {
			assertPermissionDenied(
				await setDescriptionRequest()
					.withJsonBodyParam( 'bot', true )
					.makeRequest()
			);
		} );
	} );
} );
