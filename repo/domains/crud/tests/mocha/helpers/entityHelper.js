'use strict';

const { action, utils } = require( 'api-testing' );
const {
	newSetSitelinkRequestBuilder,
	newCreateItemRequestBuilder,
	newAddPropertyStatementRequestBuilder,
	newGetPropertyRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( './RequestBuilderFactory' );

let stringPropertyId;
let testItemId;
let localSiteId;

/**
 * Creates a reusable property on the first call and returns it on subsequent calls.
 * Use this only when the existing property data does not matter.
 */

async function getStringPropertyId() {
	stringPropertyId = stringPropertyId || ( await createUniqueStringProperty() ).body.id;

	return stringPropertyId;
}

/**
 * Creates a reusable item on the first call and returns it on subsequent calls.
 * Use this only when the existing item data does not matter.
 */

async function getItemId() {
	testItemId = testItemId || ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;

	return testItemId;
}

async function deleteProperty( propertyId ) {
	const admin = await action.root();
	return admin.action( 'delete', {
		title: `Property:${propertyId}`,
		token: await admin.token()
	}, 'POST' );
}

async function createUniqueStringProperty() {
	return await newCreatePropertyRequestBuilder( {
		data_type: 'string',
		labels: { en: `string-property-${utils.uniq()}` }
	} ).makeRequest();
}

/**
 * @param {Array} statements
 *
 * @return {Object}
 */
async function createItemWithStatements( statements ) {
	return ( await newCreateItemRequestBuilder( {
		labels: { en: `item with statements ${utils.uniq()}` },
		statements: Array.isArray( statements ) ? statementListToStatementGroups( statements ) : statements
	} ).makeRequest() ).body;
}

function statementListToStatementGroups( statementList ) {
	return statementList.reduce( ( groups, statement ) => ( {
		...groups,
		[ statement.property.id ]: [ ...( groups[ statement.property.id ] || [] ), statement ]
	} ), {} );
}

/**
 * @param {Array} statements
 *
 * @return {Object}
 */
async function createPropertyWithStatements( statements ) {
	const propertyId = ( await createUniqueStringProperty() ).body.id;
	for ( const statement of statements ) {
		await newAddPropertyStatementRequestBuilder( propertyId, statement ).makeRequest();
	}

	return ( await newGetPropertyRequestBuilder( propertyId ).makeRequest() ).body;
}

/**
 * @param {string} redirectTarget - the id of the item to redirect to (target)
 * @return {Promise<string>} - the id of the item to redirect from (source)
 */
async function createRedirectForItem( redirectTarget ) {
	const redirectSource = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
	await action.getAnon().action( 'wbcreateredirect', {
		from: redirectSource,
		to: redirectTarget,
		token: '+\\'
	}, true );

	return redirectSource;
}

async function getLatestEditMetadata( entityId ) {
	const entityTitle = ( entityId.charAt( 0 ) === 'P' ) ? `Property:${entityId}` : `Item:${entityId}`;
	const editMetadata = ( await action.getAnon().action( 'query', {
		list: 'recentchanges',
		rctitle: entityTitle,
		rclimit: 1,
		rcprop: 'tags|flags|comment|ids|timestamp|user'
	} ) ).query.recentchanges[ 0 ];

	return {
		...editMetadata,
		timestamp: new Date( editMetadata.timestamp ).toUTCString()
	};
}

async function changeEntityProtectionStatus( entityId, allowedUserGroup ) {
	const admin = await action.root();
	const pageNamespace = entityId.startsWith( 'Q' ) ? 'Item' : 'Property';
	await admin.action( 'protect', {
		title: `${pageNamespace}:${entityId}`,
		token: await admin.token(),
		protections: `edit=${allowedUserGroup}`,
		expiry: 'infinite'
	}, 'POST' );
}

/**
 * @param {string} propertyId
 * @return {{property: {id: string}, value: {type: string, content: string}}}
 */
function newStatementWithRandomStringValue( propertyId ) {
	return {
		property: {
			id: propertyId
		},
		value: {
			type: 'value',
			content: 'random-string-value-' + utils.uniq()
		}
	};
}

async function getLocalSiteId() {
	localSiteId = localSiteId || ( await action.getAnon().meta(
		'wikibase',
		{ wbprop: 'siteid' }
	) ).siteid;

	return localSiteId;
}

async function createLocalSitelink( itemId, title, badges = [] ) {
	await createWikiPage( title, 'sitelink test' );
	await newSetSitelinkRequestBuilder( itemId, await getLocalSiteId(), { title, badges } )
		.makeRequest();
}

/**
 * @param {string} articleTitle
 * @param {string} text
 */
async function createWikiPage( articleTitle, text ) {
	await action.getAnon().edit( articleTitle, { text } );
}

module.exports = {
	getStringPropertyId,
	getItemId,
	deleteProperty,
	createItemWithStatements,
	createPropertyWithStatements,
	createUniqueStringProperty,
	createRedirectForItem,
	getLatestEditMetadata,
	changeEntityProtectionStatus,
	newStatementWithRandomStringValue,
	getLocalSiteId,
	createLocalSitelink,
	createWikiPage
};
