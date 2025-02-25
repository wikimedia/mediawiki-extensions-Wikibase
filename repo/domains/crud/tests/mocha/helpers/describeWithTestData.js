'use strict';

const { utils } = require( 'api-testing' );
const {
	createLocalSitelink,
	createWikiPage,
	createUniqueStringProperty,
	getLocalSiteId,
	newStatementWithRandomStringValue,
	getStringPropertyId,
	getItemId
} = require( './entityHelper' );
const { getAllowedBadges } = require( './getAllowedBadges' );
const {
	newCreateItemRequestBuilder,
	newPatchItemRequestBuilder,
	newPatchPropertyRequestBuilder
} = require( './RequestBuilderFactory' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );

/**
 * `describeWithTestData` is intended for testing behaviors across multiple related route categories
 * (e.g., all routes, all edit routes, or all GET routes) that share the same test data.
 *
 * **When to use:**
 * - When tests span a wide range of routes that require the same setup.
 * - When the consistency of shared test data across multiple describe blocks is crucial.
 *
 * **When NOT to use:**
 * - For testing a small number of specific routes or individual behaviors.
 * - When the indirection or built-in resets introduce unnecessary complexity or overhead.
 *
 * @param {string} testName
 * @param {Function} runAllTests
 * @param {boolean} createNewItem
 * @return {void}
 */
function describeWithTestData( testName, runAllTests, createNewItem = false ) {
	const itemRequestInputs = {};
	const propertyRequestInputs = {};
	const originalLinkedArticle = utils.title( 'Original-article-linked-to-test-item' );
	const newLinkedArticle = utils.title( 'New-article-linked-to-test-item' );

	async function resetEntityTestData( id, statementPropertyId ) {
		const isItem = id.startsWith( 'Q' );
		const patchEntity = isItem ? newPatchItemRequestBuilder : newPatchPropertyRequestBuilder;
		const patchEntityResponse = await patchEntity( id, [
			{ op: 'add', path: '/labels/en', value: `entity-with-statements-${utils.uniq()}` },
			{ op: 'add', path: '/descriptions/en', value: `entity-with-statements-${utils.uniq()}` },
			{ op: 'add', path: '/aliases/en', value: [ 'entity', 'thing' ] },
			{
				op: 'add',
				path: `/statements/${statementPropertyId}`,
				value: [ newStatementWithRandomStringValue( statementPropertyId ) ]
			}
		] ).makeRequest();
		expect( patchEntityResponse ).to.have.status( 200 );

		if ( isItem ) {
			await createLocalSitelink( id, originalLinkedArticle, [ ( await getAllowedBadges() )[ 0 ] ] );
		}

		return patchEntityResponse.body;
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
			const statementPropertyId = await getStringPropertyId();

			const itemId = createNewItem ?
				( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id : await getItemId();
			const item = await resetEntityTestData( itemId, statementPropertyId, originalLinkedArticle );
			itemRequestInputs.mainTestSubject = itemId;
			itemRequestInputs.itemId = itemId;
			itemRequestInputs.statementId = item.statements[ statementPropertyId ][ 0 ].id;
			itemRequestInputs.statementPropertyId = statementPropertyId;
			itemRequestInputs.siteId = await getLocalSiteId();
			itemRequestInputs.linkedArticle = newLinkedArticle;

			const propertyId = ( await createUniqueStringProperty() ).body.id;
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
