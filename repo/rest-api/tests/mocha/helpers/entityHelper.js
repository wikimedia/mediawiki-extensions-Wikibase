'use strict';

const { action, utils } = require( 'api-testing' );

async function createEntity( type, entity ) {
	return action.getAnon().action( 'wbeditentity', {
		new: type,
		token: '+\\',
		data: JSON.stringify( entity )
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
 */
async function createItemWithStatements( statements ) {
	statements.forEach( ( statement ) => {
		statement.type = 'statement';
	} );
	const item = {
		claims: statements
	};
	return await createEntity( 'item', item );
}

async function createSingleItem() {
	const stringPropertyId = ( await createUniqueStringProperty() ).entity.id;
	const siteId = ( await action.getAnon().meta(
		'wikibase',
		{ wbprop: 'siteid' }
	) ).siteid;
	const pageWithSiteLink = utils.title( 'SiteLink Test' );
	await action.getAnon().edit( pageWithSiteLink, { text: 'sitelink test' } );

	const item = {
		labels: { en: { language: 'en', value: `non-empty-item-${utils.uniq()}` } },
		descriptions: { en: { language: 'en', value: 'non-empty-item-description' } },
		aliases: { en: [ { language: 'en', value: 'non-empty-item-alias' } ] },
		sitelinks: {
			[ siteId ]: {
				site: siteId,
				title: pageWithSiteLink
			}
		},
		claims: [
			{ // with value, without qualifiers or references
				mainsnak: {
					snaktype: 'value',
					property: stringPropertyId,
					datavalue: { value: 'im a statement value', type: 'string' }
				}, type: 'statement', rank: 'normal'
			},
			{ // no value, with qualifier and reference
				mainsnak: {
					snaktype: 'novalue',
					property: stringPropertyId
				},
				type: 'statement',
				rank: 'normal',
				qualifiers: [
					{
						snaktype: 'value',
						property: stringPropertyId,
						datavalue: { value: 'im a qualifier value', type: 'string' }
					}
				],
				references: [ {
					snaks: [ {
						snaktype: 'value',
						property: stringPropertyId,
						datavalue: { value: 'im a reference value', type: 'string' }
					} ]
				} ]
			}
		]
	};

	return await createEntity( 'item', item );
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

async function getLatestEditMetadata( itemId ) {
	const editMetadata = ( await action.getAnon().action( 'query', {
		list: 'recentchanges',
		rctitle: `Item:${itemId}`,
		rclimit: 1,
		rcprop: 'tags|flags|comment|ids|timestamp|user'
	} ) ).query.recentchanges[ 0 ];

	return {
		...editMetadata,
		timestamp: new Date( editMetadata.timestamp ).toUTCString()
	};
}

async function protectItem( itemId ) {
	const mindy = await action.mindy();
	await mindy.action( 'protect', {
		title: `Item:${itemId}`,
		token: await mindy.token(),
		protections: 'edit=sysop',
		expiry: 'infinite'
	}, 'POST' );
}

function newStatementSerializationWithRandomStringValue( property ) {
	return {
		mainsnak: {
			snaktype: 'value',
			datavalue: {
				type: 'string',
				value: 'random-string-value-' + utils.uniq()
			},
			property
		}
	};
}

module.exports = {
	createEntity,
	createSingleItem,
	createItemWithStatements,
	createUniqueStringProperty,
	createRedirectForItem,
	getLatestEditMetadata,
	protectItem,
	newStatementWithRandomStringValue: newStatementSerializationWithRandomStringValue
};
