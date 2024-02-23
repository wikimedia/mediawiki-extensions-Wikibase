'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createUniqueStringProperty,
	newLegacyStatementWithRandomStringValue,
	createEntity,
	editEntity,
	createLocalSitelink,
	getLocalSiteId, createWikiPage
} = require( '../helpers/entityHelper' );
const {
	editRequestsOnItem,
	editRequestsOnProperty
} = require( '../helpers/happyPathRequestBuilders' );
const entityHelper = require( '../helpers/entityHelper' );
const { getAllowedBadges } = require( '../helpers/getAllowedBadges' );

describe( 'IP masking', () => {

	const itemRequestInputs = {};
	const propertyRequestInputs = {};
	const originalLinkedArticle = utils.title( 'Article-linked-to-test-item' );
	const newLinkedArticle = utils.title( 'Article-linked-to-test-item' );

	function withTempUserConfig( newRequestBuilder, config ) {
		return newRequestBuilder().withHeader( 'X-Wikibase-Ci-Tempuser-Config', JSON.stringify( config ) );
	}

	async function resetEntityTestData( id, statementPropertyId ) {
		if ( id.startsWith( 'Q' ) ) {
			await createLocalSitelink( id, originalLinkedArticle, [ ( await getAllowedBadges() )[ 0 ] ] );
		}

		return ( await editEntity( id, {
			labels: [ { language: 'en', value: `entity-with-statements-${utils.uniq()}` } ],
			descriptions: [ { language: 'en', value: `entity-with-statements-${utils.uniq()}` } ],
			aliases: [ { language: 'en', value: 'entity' }, { language: 'en', value: 'thing' } ],
			claims: [ newLegacyStatementWithRandomStringValue( statementPropertyId ) ]
		} ) ).entity;
	}

	before( async () => {
		await createWikiPage( newLinkedArticle, 'sitelink test' );
		const statementPropertyId = ( await createUniqueStringProperty() ).entity.id;

		const itemId = ( await createEntity( 'item', {} ) ).entity.id;
		const itemData = await resetEntityTestData( itemId, statementPropertyId );

		itemRequestInputs.mainTestSubject = itemId;
		itemRequestInputs.itemId = itemId;
		itemRequestInputs.statementId = itemData.claims[ statementPropertyId ][ 0 ].id;
		itemRequestInputs.statementPropertyId = statementPropertyId;
		itemRequestInputs.siteId = await getLocalSiteId();
		itemRequestInputs.linkedArticle = newLinkedArticle;

		const propertyId = ( await createUniqueStringProperty() ).entity.id;
		const propertyData = await resetEntityTestData( propertyId, statementPropertyId );
		propertyRequestInputs.mainTestSubject = propertyId;
		propertyRequestInputs.propertyId = propertyId;
		propertyRequestInputs.statementId = propertyData.claims[ statementPropertyId ][ 0 ].id;
		propertyRequestInputs.statementPropertyId = statementPropertyId;
	} );

	const useRequestInputs = ( requestInputs ) => ( newReqBuilder ) => ( {
		newRequestBuilder: () => newReqBuilder( requestInputs ),
		requestInputs
	} );

	const editRequestsWithInputs = [
		...editRequestsOnItem.map( useRequestInputs( itemRequestInputs ) ),
		...editRequestsOnProperty.map( useRequestInputs( propertyRequestInputs ) )
	];

	editRequestsWithInputs.forEach( ( { newRequestBuilder, requestInputs } ) => {
		describe( newRequestBuilder().getRouteDescription(), () => {
			afterEach( async () => {
				if ( newRequestBuilder().getMethod() === 'DELETE' ||
					newRequestBuilder().getRouteDescription().includes( 'sitelinks' ) ) {
					const entityData = await resetEntityTestData(
						requestInputs.mainTestSubject,
						requestInputs.statementPropertyId
					);
					requestInputs.statementId = entityData.claims[ requestInputs.statementPropertyId ][ 0 ].id;
				}
			} );

			it( 'makes an edit as an IP user with tempUser disabled', async () => {
				const response = await withTempUserConfig( newRequestBuilder, { enabled: false } )
					.makeRequest();

				expect( response ).status.to.be.within( 200, 299 );
				const { user } = await entityHelper.getLatestEditMetadata( requestInputs.mainTestSubject );
				assert.match( user, /^\d+\.\d+\.\d+\.\d+$/ );
			} );

			it( 'makes an edit as a temp user with tempUser enabled', async () => {
				const tempUserPrefix = 'TempUserTest';
				const response = await withTempUserConfig(
					newRequestBuilder,
					{ enabled: true, genPattern: `${tempUserPrefix} $1` }
				).makeRequest();

				expect( response ).status.to.be.within( 200, 299 );
				const { user } = await entityHelper.getLatestEditMetadata( requestInputs.mainTestSubject );
				assert.include( user, tempUserPrefix );
			} );
		} );
	} );
} );
