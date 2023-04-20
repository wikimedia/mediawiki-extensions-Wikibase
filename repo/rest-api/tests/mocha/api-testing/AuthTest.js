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

describe( 'Auth', () => {

	let itemId;
	let statementId;
	let stringPropertyId;
	let user;

	before( async () => {
		stringPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const createEntityResponse = await createEntity(
			'item',
			{
				claims: [ newLegacyStatementWithRandomStringValue( stringPropertyId ) ],
				descriptions: { en: { language: 'en', value: `item-with-statements-${utils.uniq()}` } },
				labels: { en: { language: 'en', value: `item-with-statements-${utils.uniq()}` } },
				aliases: {
					en: [ { language: 'en', value: 'Douglas Noël Adams' }, { language: 'en', value: 'DNA' } ]
				}
			}
		);
		itemId = createEntityResponse.entity.id;
		statementId = createEntityResponse.entity.claims[ stringPropertyId ][ 0 ].id;
		user = await action.mindy();
	} );

	const editRequests = [
		() => rbf.newAddItemStatementRequestBuilder(
			itemId,
			newStatementWithRandomStringValue( stringPropertyId )
		),
		() => rbf.newReplaceItemStatementRequestBuilder(
			itemId,
			statementId,
			newStatementWithRandomStringValue( stringPropertyId )
		),
		() => rbf.newReplaceStatementRequestBuilder(
			statementId,
			newStatementWithRandomStringValue( stringPropertyId )
		),
		() => rbf.newRemoveItemStatementRequestBuilder( itemId, statementId ),
		() => rbf.newRemoveStatementRequestBuilder( statementId ),
		() => rbf.newPatchItemStatementRequestBuilder(
			itemId,
			statementId,
			[ {
				op: 'replace',
				path: '/value/content',
				value: 'random-string-value-' + utils.uniq()
			} ]
		),
		() => rbf.newPatchStatementRequestBuilder(
			statementId,
			[ {
				op: 'replace',
				path: '/value/content',
				value: 'random-string-value-' + utils.uniq()
			} ]
		),
		() => rbf.newSetItemLabelRequestBuilder( itemId, 'en', `english label ${utils.uniq()}` )
	];

	const setDescriptionRequest = () => rbf.newSetItemDescriptionRequestBuilder(
		itemId,
		'en',
		'random-test-description-' + utils.uniq()
	);

	[
		() => rbf.newGetItemStatementsRequestBuilder( itemId ),
		() => rbf.newGetItemStatementRequestBuilder( itemId, statementId ),
		() => rbf.newGetItemRequestBuilder( itemId ),
		() => rbf.newGetItemAliasesInLanguageRequestBuilder( itemId, 'en' ),
		() => rbf.newGetItemAliasesRequestBuilder( itemId ),
		() => rbf.newGetItemDescriptionRequestBuilder( itemId, 'en' ),
		() => rbf.newGetItemDescriptionsRequestBuilder( itemId ),
		() => rbf.newGetItemLabelRequestBuilder( itemId, 'en' ),
		() => rbf.newGetItemLabelsRequestBuilder( itemId ),
		() => rbf.newGetStatementRequestBuilder( statementId ),

		// TODO: move into editRequests, once Authorization works
		setDescriptionRequest,
		...editRequests
	].forEach( ( newRequestBuilder ) => {
		describe( `Authentication - ${newRequestBuilder().getRouteDescription()}`, () => {

			afterEach( async () => {
				if ( newRequestBuilder().getMethod() === 'DELETE' ) {
					statementId = ( await rbf.newAddItemStatementRequestBuilder(
						itemId,
						newStatementWithRandomStringValue( stringPropertyId )
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

		editRequests.forEach( ( newRequestBuilder ) => {
			describe( 'Protected item', () => {
				before( async () => {
					await changeItemProtectionStatus( itemId, 'sysop' ); // protect
				} );

				after( async () => {
					await changeItemProtectionStatus( itemId, 'all' ); // unprotect
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
