'use strict';

const { action, utils } = require( 'api-testing' );
const { newSetSitelinkRequestBuilder } = require( './RequestBuilderFactory' );

async function makeEditEntityRequest( params, entity ) {
	return action.getAnon().action( 'wbeditentity', {
		token: '+\\',
		data: JSON.stringify( entity ),
		...params
	}, 'POST' );
}

async function createEntity( type, entity ) {
	return makeEditEntityRequest( { new: type }, entity );
}

async function editEntity( id, entityData, clear = false ) {
	return makeEditEntityRequest( { id, clear }, entityData );
}

async function deleteProperty( propertyId ) {
	const admin = await action.root();
	return admin.action( 'delete', {
		title: `Property:${propertyId}`,
		token: await admin.token()
	}, 'POST' );
}

async function createUniqueStringProperty() {
	return await createEntity( 'property', {
		labels: { en: { language: 'en', value: `string-property-${utils.uniq()}` } },
		datatype: 'string'
	} );
}

/**
 * @param {Array} statements
 * @param {string} entityType
 *
 * @return {Object}
 */
async function createEntityWithStatements( statements, entityType ) {
	statements.forEach( ( statement ) => {
		statement.type = 'statement';
	} );

	const entity = { claims: statements };
	if ( entityType === 'property' ) {
		entity.datatype = 'string';
	}

	return await createEntity( entityType, entity );
}

/**
 * @param {Array} statements
 *
 * @return {Object}
 */
async function createItemWithStatements( statements ) {
	return await createEntityWithStatements( statements, 'item' );
}

/**
 * @param {Array} statements
 *
 * @return {Object}
 */
async function createPropertyWithStatements( statements ) {
	return await createEntityWithStatements( statements, 'property' );
}

/**
 * @param {string} redirectTarget - the id of the item to redirect to (target)
 * @return {Promise<string>} - the id of the item to redirect from (source)
 */
async function createRedirectForItem( redirectTarget ) {
	const redirectSource = ( await createEntity( 'item', {} ) ).entity.id;
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
 * @return {{mainsnak: {datavalue: {type: string, value: string}, property: string, snaktype: string}}}
 */
function newLegacyStatementWithRandomStringValue( propertyId ) {
	return {
		mainsnak: {
			snaktype: 'value',
			datavalue: {
				type: 'string',
				value: 'random-string-value-' + utils.uniq()
			},
			property: propertyId
		},
		type: 'statement'
	};
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
	return ( await action.getAnon().meta(
		'wikibase',
		{ wbprop: 'siteid' }
	) ).siteid;
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
	createEntity,
	editEntity,
	deleteProperty,
	createEntityWithStatements,
	createItemWithStatements,
	createPropertyWithStatements,
	createUniqueStringProperty,
	createRedirectForItem,
	getLatestEditMetadata,
	changeEntityProtectionStatus,
	newStatementWithRandomStringValue,
	newLegacyStatementWithRandomStringValue,
	getLocalSiteId,
	createLocalSitelink,
	createWikiPage
};
