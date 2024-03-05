'use strict';

const { utils } = require( 'api-testing' );
const {
	getLatestEditMetadata,
	createLocalSitelink,
	editEntity,
	newLegacyStatementWithRandomStringValue,
	createWikiPage,
	createUniqueStringProperty,
	createEntity,
	getLocalSiteId
} = require( './entityHelper' );
const { getAllowedBadges } = require( './getAllowedBadges' );

function describeWithTestData( testName, runAllTests ) {
	const itemRequestInputs = {};
	const propertyRequestInputs = {};
	const originalLinkedArticle = utils.title( 'Original-article-linked-to-test-item' );
	const newLinkedArticle = utils.title( 'New-article-linked-to-test-item' );

	async function resetEntityTestData( id, statementPropertyId ) {
		const editEntityResponse = await editEntity( id, {
			labels: [ { language: 'en', value: `entity-with-statements-${utils.uniq()}` } ],
			descriptions: [ { language: 'en', value: `entity-with-statements-${utils.uniq()}` } ],
			aliases: [ { language: 'en', value: 'entity' }, { language: 'en', value: 'thing' } ],
			claims: { [ statementPropertyId ]: [ newLegacyStatementWithRandomStringValue( statementPropertyId ) ] }
		}, true );
		if ( id.startsWith( 'Q' ) ) {
			await createLocalSitelink( id, originalLinkedArticle, [ ( await getAllowedBadges() )[ 0 ] ] );
		}
		const revision = await getLatestEditMetadata( id );

		return {
			entity: editEntityResponse.entity,
			latestRevId: revision.revid,
			latestRevTimestamp: revision.timestamp
		};
	}

	function describeEachRouteWithReset( routes, runForEachRoute ) {
		routes.forEach( ( { newRequestBuilder, requestInputs } ) => {
			describe( newRequestBuilder().getRouteDescription(), () => {
				afterEach( async () => {
					if ( newRequestBuilder().getMethod() === 'DELETE' ||
						( newRequestBuilder().getRouteDescription().includes( 'sitelinks' ) &&
						newRequestBuilder().getMethod() !== 'GET' ) ) {
						const entityData = await resetEntityTestData(
							requestInputs.mainTestSubject,
							requestInputs.statementPropertyId
						);
						requestInputs.statementId =
							entityData.entity.claims[ requestInputs.statementPropertyId ][ 0 ].id;
						requestInputs.latestRevTimestamp = entityData.latestRevTimestamp;
						requestInputs.latestRevId = entityData.latestRevId;
					}
				} );

				runForEachRoute( newRequestBuilder, requestInputs );
			} );
		} );
	}

	describe( testName, () => {
		before( async () => {
			await createWikiPage( newLinkedArticle, 'sitelink test' );
			const statementPropertyId = ( await createUniqueStringProperty() ).entity.id;

			const itemId = ( await createEntity( 'item', {} ) ).entity.id;
			const itemData = await resetEntityTestData( itemId, statementPropertyId, originalLinkedArticle );
			itemRequestInputs.latestRevTimestamp = itemData.latestRevTimestamp;
			itemRequestInputs.latestRevId = itemData.latestRevId;

			itemRequestInputs.mainTestSubject = itemId;
			itemRequestInputs.itemId = itemId;
			itemRequestInputs.statementId = itemData.entity.claims[ statementPropertyId ][ 0 ].id;
			itemRequestInputs.statementPropertyId = statementPropertyId;
			itemRequestInputs.siteId = await getLocalSiteId();
			itemRequestInputs.linkedArticle = newLinkedArticle;

			const propertyId = ( await createUniqueStringProperty() ).entity.id;
			const propertyData = await resetEntityTestData( propertyId, statementPropertyId );
			propertyRequestInputs.latestRevTimestamp = propertyData.latestRevTimestamp;
			propertyRequestInputs.latestRevId = propertyData.latestRevId;

			propertyRequestInputs.mainTestSubject = propertyId;
			propertyRequestInputs.propertyId = propertyId;
			propertyRequestInputs.statementId = propertyData.entity.claims[ statementPropertyId ][ 0 ].id;
			propertyRequestInputs.statementPropertyId = statementPropertyId;

		} );

		runAllTests( itemRequestInputs, propertyRequestInputs, describeEachRouteWithReset );
	} );
}

// eslint-disable-next-line mocha/no-exports
module.exports = { describeWithTestData };
