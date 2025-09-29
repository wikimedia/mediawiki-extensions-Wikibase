const { defineStore } = require( 'pinia' );
const { snakValueHtmlForHash, updateSnakValueHtmlForHash } = require( './serverRenderedHtml.js' );
const { renderSnakValueHtml } = require( '../api/editEntity.js' );

const useStatementsStore = defineStore( 'statements', {
	state: () => ( {
		statements: new Map(),
		properties: new Map()
	} ),
	actions: {
		populateWithClaims( claims, renderMissingHtml = false ) {
			this.statements = new Map();
			this.properties = new Map();
			const snaksWithoutHtml = [];
			for ( const [ propertyId, statementList ] of Object.entries( claims ) ) {
				const statementIdList = [];
				for ( const statement of statementList ) {
					this.statements.set( statement.id, statement );
					statementIdList.push( statement.id );
					if ( 'hash' in statement.mainsnak && !snakValueHtmlForHash( statement.mainsnak.hash ) ) {
						snaksWithoutHtml.push( statement.mainsnak );
					}
					if ( statement.qualifiers ) {
						for ( const qualifierPropertyId in statement.qualifiers ) {
							for ( const qualifier of statement.qualifiers[ qualifierPropertyId ] ) {
								if ( 'hash' in qualifier && !snakValueHtmlForHash( qualifier.hash ) ) {
									snaksWithoutHtml.push( qualifier );
								}
							}
						}
					}
				}
				this.properties.set( propertyId, statementIdList );
			}
			if ( !renderMissingHtml ) {
				return Promise.resolve();
			}

			snaksWithoutHtml.filter( ( snak ) => snak.snaktype !== 'value' )
				.forEach( ( snak ) => {
					const messageKey = 'wikibase-snakview-variations-' + snak.snaktype + '-label';
					// messages that can appear here:
					// * wikibase-snakview-variations-novalue-label
					// * wikibase-snakview-variations-somevalue-label
					updateSnakValueHtmlForHash(
						snak.hash,
						'<span class="wikibase-snakview-variation-' + snak.snaktype + 'snak">' + mw.msg( messageKey ) + '</span>'
					);
				} );
			return Promise.all(
				snaksWithoutHtml
					.filter( ( snak ) => snak.snaktype === 'value' )
					.map(
						( snak ) => renderSnakValueHtml( snak.datavalue )
						.then( ( result ) => updateSnakValueHtmlForHash( snak.hash, result ) )
					)
			);
		}
	}
} );

const getPropertyIds = function () {
	const statementsStore = useStatementsStore();
	return statementsStore.properties.keys();
};

/**
 * @param{string} propertyId
 * @returns {*}
 */
const getStatementsForProperty = function ( propertyId ) {
	const statementsStore = useStatementsStore();
	return statementsStore.properties.get( propertyId ).map(
		( statementId ) => statementsStore.statements.get( statementId )
	);
};

/**
 * @param{string} statementId
 * @returns {*}
 */
const getStatementById = function ( statementId ) {
	const statementsStore = useStatementsStore();
	return statementsStore.statements.get( statementId );
};

const setStatementIdsForProperty = function ( propertyId, statementIds ) {
	const statementsStore = useStatementsStore();
	statementsStore.properties.set( propertyId, statementIds );
};

module.exports = {
	useStatementsStore,
	getPropertyIds,
	getStatementsForProperty,
	getStatementById,
	setStatementIdsForProperty
};
