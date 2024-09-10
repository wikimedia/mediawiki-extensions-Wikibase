'use strict';

const { utils } = require( 'api-testing' );
const {
	createLocalSitelink,
	createWikiPage,
	createUniqueStringProperty,
	createEntity,
	getLocalSiteId, newStatementWithRandomStringValue
} = require( './entityHelper' );
const { getAllowedBadges } = require( './getAllowedBadges' );
const { newPatchItemRequestBuilder, newPatchPropertyRequestBuilder } = require( './RequestBuilderFactory' );

function describeWithTestData( testName, runAllTests ) {
	const itemRequestInputs = {};
	const propertyRequestInputs = {};
	const originalLinkedArticle = utils.title( 'Original-article-linked-to-test-item' );
	const newLinkedArticle = utils.title( 'New-article-linked-to-test-item' );

	async function resetEntityTestData( id, statementPropertyId ) {
		const isItem = id.startsWith( 'Q' );
		const patchEntity = isItem ? newPatchItemRequestBuilder : newPatchPropertyRequestBuilder;
		const editEntityResponse = await patchEntity( id, [
			{ op: 'add', path: '/labels/en', value: `entity-with-statements-${utils.uniq()}` },
			{ op: 'add', path: '/descriptions/en', value: `entity-with-statements-${utils.uniq()}` },
			{ op: 'add', path: '/aliases/en', value: [ 'entity', 'thing' ] },
			{
				op: 'add',
				path: `/statements/${statementPropertyId}`,
				value: [ newStatementWithRandomStringValue( statementPropertyId ) ]
			}
		] ).makeRequest();
		if ( isItem ) {
			await createLocalSitelink( id, originalLinkedArticle, [ ( await getAllowedBadges() )[ 0 ] ] );
		}

		return editEntityResponse.body;
	}

	function describeEachRouteWithReset( routes, runForEachRoute ) {
		routes.forEach( ( { newRequestBuilder, requestInputs } ) => {
			describe( newRequestBuilder().getRouteDescription(), () => {
				afterEach( async () => {
					if ( newRequestBuilder().getMethod() === 'DELETE' ||
						( newRequestBuilder().getRouteDescription().toLowerCase().includes( 'sitelink' ) &&
						newRequestBuilder().getMethod() !== 'GET' ) ) {
						const entity = await resetEntityTestData(
							requestInputs.mainTestSubject,
							requestInputs.statementPropertyId
						);
						requestInputs.statementId = entity.statements[ requestInputs.statementPropertyId ][ 0 ].id;
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
			const item = await resetEntityTestData( itemId, statementPropertyId, originalLinkedArticle );
			itemRequestInputs.mainTestSubject = itemId;
			itemRequestInputs.itemId = itemId;
			itemRequestInputs.statementId = item.statements[ statementPropertyId ][ 0 ].id;
			itemRequestInputs.statementPropertyId = statementPropertyId;
			itemRequestInputs.siteId = await getLocalSiteId();
			itemRequestInputs.linkedArticle = newLinkedArticle;

			const propertyId = ( await createUniqueStringProperty() ).entity.id;
			const property = await resetEntityTestData( propertyId, statementPropertyId );
			propertyRequestInputs.mainTestSubject = propertyId;
			propertyRequestInputs.propertyId = propertyId;
			propertyRequestInputs.statementId = property.statements[ statementPropertyId ][ 0 ].id;
			propertyRequestInputs.statementPropertyId = statementPropertyId;

		} );

		runAllTests( itemRequestInputs, propertyRequestInputs, describeEachRouteWithReset );
	} );
}

// eslint-disable-next-line mocha/no-exports
module.exports = { describeWithTestData };
