'use strict';

const { action, utils } = require( 'api-testing' );

async function createEntity( type, entity ) {
	return action.getAnon().action( 'wbeditentity', {
		new: type,
		token: '+\\',
		data: JSON.stringify( entity )
	}, 'POST' );
}

async function deleteProperty( propertyId ) {
	const admin = await action.mindy();
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
 */
async function createItemWithStatements( statements ) {
	return await createEntityWithStatements( statements, 'item' );
}

/**
 * @param {Array} statements
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

async function changeItemProtectionStatus( itemId, allowedUserGroup ) {
	const mindy = await action.mindy();
	await mindy.action( 'protect', {
		title: `Item:${itemId}`,
		token: await mindy.token(),
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

module.exports = {
	createEntity,
	deleteProperty,
	createEntityWithStatements,
	createItemWithStatements,
	createPropertyWithStatements,
	createUniqueStringProperty,
	createRedirectForItem,
	getLatestEditMetadata,
	changeItemProtectionStatus,
	newStatementWithRandomStringValue,
	newLegacyStatementWithRandomStringValue
};
