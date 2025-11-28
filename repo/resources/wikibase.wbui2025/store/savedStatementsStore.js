const { defineStore } = require( 'pinia' );
const {
	propertyLinkHtml,
	updatePropertyLinkHtml,
	snakValueHtmlForHash,
	updateSnakValueHtmlForHash
} = require( './serverRenderedHtml.js' );
const { renderPropertyLinkHtml, renderSnakValueHtml } = require( '../api/editEntity.js' );

const useSavedStatementsStore = defineStore( 'savedStatements', {
	state: () => ( {
		statements: new Map(),
		properties: new Map()
	} ),
	actions: {
		async populateWithClaims( claims, renderMissingHtml = false ) {
			this.statements = new Map();
			this.properties = new Map();
			const snaksWithoutHtml = [];
			const propertiesWithoutHtml = new Set();
			for ( const [ propertyId, statementList ] of Object.entries( claims ) ) {
				if ( !propertyLinkHtml( propertyId ) ) {
					propertiesWithoutHtml.add( propertyId );
				}
				const statementIdList = [];
				for ( const statement of statementList ) {
					this.statements.set( statement.id, statement );
					statementIdList.push( statement.id );
					if ( 'hash' in statement.mainsnak && !snakValueHtmlForHash( statement.mainsnak.hash ) ) {
						snaksWithoutHtml.push( statement.mainsnak );
					}
					if ( statement.qualifiers ) {
						for ( const qualifierPropertyId in statement.qualifiers ) {
							if ( !propertyLinkHtml( qualifierPropertyId ) ) {
								propertiesWithoutHtml.add( qualifierPropertyId );
							}
							for ( const qualifier of statement.qualifiers[ qualifierPropertyId ] ) {
								if ( 'hash' in qualifier && !snakValueHtmlForHash( qualifier.hash ) ) {
									snaksWithoutHtml.push( qualifier );
								}
							}
						}
					}
					if ( statement.references ) {
						for ( const reference of statement.references ) {
							const snaks = reference.snaks;
							if ( !snaks || typeof snaks !== 'object' ) {
								continue;
							}

							for ( const referenceProperty in snaks ) {
								if ( !snaks[ referenceProperty ].length ) {
									continue;
								}
								if ( !propertyLinkHtml( referenceProperty ) ) {
									propertiesWithoutHtml.add( referenceProperty );
								}

								for ( const snak of reference.snaks[ referenceProperty ] ) {
									if ( 'hash' in snak && !snakValueHtmlForHash( snak.hash ) ) {
										snaksWithoutHtml.push( snak );
									}
								}
							}
						}
					}

				}
				this.properties.set( propertyId, statementIdList );
			}

			if ( !renderMissingHtml ) {
				return;
			}

			if ( propertiesWithoutHtml.size > 0 ) {
				const propertyHtml = await renderPropertyLinkHtml( Array.from( propertiesWithoutHtml ) );
				updatePropertyLinkHtml( propertyHtml );
			}

			for ( const snak of snaksWithoutHtml ) {
				if ( snak.snaktype === 'value' ) {
					const snakValueHtml = await renderSnakValueHtml( snak.datavalue, snak.property );
					updateSnakValueHtmlForHash( snak.hash, snakValueHtml );
				} else {
					const messageKey = 'wikibase-snakview-variations-' + snak.snaktype + '-label';
					// messages that can appear here:
					// * wikibase-snakview-variations-novalue-label
					// * wikibase-snakview-variations-somevalue-label
					updateSnakValueHtmlForHash(
						snak.hash,
						'<span class="wikibase-snakview-variation-' + snak.snaktype + 'snak">' + mw.msg( messageKey ) + '</span>'
					);
				}
			}
		}
	}
} );

const getPropertyIds = function () {
	const statementsStore = useSavedStatementsStore();
	return statementsStore.properties.keys();
};

/**
 * @param{string} propertyId
 * @returns {*}
 */
const getStatementsForProperty = function ( propertyId ) {
	const statementsStore = useSavedStatementsStore();
	return statementsStore.properties.get( propertyId ).map(
		( statementId ) => statementsStore.statements.get( statementId )
	);
};

/**
 * @param{string} statementId
 * @returns {*}
 */
const getStatementById = function ( statementId ) {
	const statementsStore = useSavedStatementsStore();
	return statementsStore.statements.get( statementId );
};

const setStatementIdsForProperty = function ( propertyId, statementIds ) {
	const statementsStore = useSavedStatementsStore();
	statementsStore.properties.set( propertyId, statementIds );
};

module.exports = {
	useSavedStatementsStore,
	getPropertyIds,
	getStatementsForProperty,
	getStatementById,
	setStatementIdsForProperty
};
