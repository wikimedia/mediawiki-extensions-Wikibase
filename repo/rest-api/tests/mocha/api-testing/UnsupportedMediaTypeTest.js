'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createEntityWithStatements,
	createUniqueStringProperty,
	newLegacyStatementWithRandomStringValue,
	createLocalSitelink,
	getLocalSiteId
} = require( '../helpers/entityHelper' );
const {
	editRequestsOnItem,
	editRequestsOnProperty
} = require( '../helpers/happyPathRequestBuilders' );

describe( 'Unsupported media type requests', () => {

	const itemRequestInputs = {};
	const propertyRequestInputs = {};

	before( async () => {
		const statementPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const linkedArticle = utils.title( 'Article-linked-to-test-item' );

		const createItemResponse = await createEntityWithStatements(
			[ newLegacyStatementWithRandomStringValue( statementPropertyId ) ],
			'item'
		);
		itemRequestInputs.itemId = createItemResponse.entity.id;
		itemRequestInputs.statementId = createItemResponse.entity.claims[ statementPropertyId ][ 0 ].id;
		itemRequestInputs.statementPropertyId = statementPropertyId;

		await createLocalSitelink( createItemResponse.entity.id, linkedArticle );
		itemRequestInputs.siteId = await getLocalSiteId();

		const createPropertyResponse = await createEntityWithStatements(
			[ newLegacyStatementWithRandomStringValue( statementPropertyId ) ],
			'property'
		);
		propertyRequestInputs.propertyId = createPropertyResponse.entity.id;
		propertyRequestInputs.statementId = createPropertyResponse.entity.claims[ statementPropertyId ][ 0 ].id;
		propertyRequestInputs.statementPropertyId = statementPropertyId;
	} );

	const useRequestInputs = ( requestInputs ) => ( newReqBuilder ) => () => newReqBuilder( requestInputs );

	[
		...editRequestsOnItem.map( useRequestInputs( itemRequestInputs ) ),
		...editRequestsOnProperty.map( useRequestInputs( propertyRequestInputs ) )
	].forEach( ( newRequestBuilder ) => {
		it( `${newRequestBuilder().getRouteDescription()} responds 415 for an unsupported media type`, async () => {
			const contentType = 'multipart/form-data';
			const response = await newRequestBuilder()
				.withHeader( 'content-type', contentType )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 415 );
			assert.strictEqual( response.body.message, `Unsupported Content-Type: '${contentType}'` );
		} );
	} );
} );
