const { defineStore } = require( 'pinia' );
const {
	propertyLinkHtml,
	updatePropertyLinkHtml,
	snakValueHtmlForHash,
	updateSnakValueHtmlForHash,
	snakValueHtmlForHashHasError
} = require( './serverRenderedHtml.js' );
const { renderPropertyLinkHtml, renderSnakValueHtml } = require( '../api/editEntity.js' );

const useSavedStatementsStore = defineStore( 'savedStatements', {
	state: () => ( {
		statements: new Map(),
		properties: new Map(),
		propertyIdToStatementSection: new Map(),
		/** statementId -> HTML string */
		indicatorHtmlForMainSnaks: new Map(),
		/** `${ statementId }|${ snakHash }` -> HTML string */
		indicatorHtmlForQualifiers: new Map(),
		/** `${ statementId }|${ referenceHash }|${ snakHash }` -> HTML string */
		indicatorHtmlForReferenceSnaks: new Map(),
		/** statementId -> object[] */
		popoverHtmlForMainSnaks: new Map(),
		/** `${ statementId }|${ snakHash }` -> object[] */
		popoverHtmlForQualifiers: new Map(),
		/** `${ statementId }|${ referenceHash }|${ snakHash }` -> object[] */
		popoverHtmlForReferenceSnaks: new Map()
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

			// Clear out properties from the statements section that no longer have claims.
			// We don't have information about the statement grouping, so we cannot rebuild the
			// `propertiesForStatementSection` Map here.
			for ( const propertyId of this.propertyIdToStatementSection.keys() ) {
				if ( !this.properties.get( propertyId ) ) {
					this.propertyIdToStatementSection.delete( propertyId );
				}
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
		},
		setPropertyIdsForStatementSection( statementSection, propertyIds ) {
			for ( const propertyId of propertyIds ) {
				this.propertyIdToStatementSection.set( propertyId, statementSection );
			}
		}
	}
} );

const getPropertyIds = function () {
	const statementsStore = useSavedStatementsStore();
	return statementsStore.properties.keys();
};

const getPropertyIdsForStatementSection = function ( targetSection ) {
	const statementsStore = useSavedStatementsStore();
	const propertyIds = [];
	for ( const [ propertyId, statementSection ] of statementsStore.propertyIdToStatementSection ) {
		if ( statementSection === targetSection && statementsStore.properties.get( propertyId ) ) {
			propertyIds.push( propertyId );
		}
	}
	return propertyIds;
};

const setStatementSectionForPropertyId = function ( propertyId, statementSection ) {
	const statementsStore = useSavedStatementsStore();
	statementsStore.propertyIdToStatementSection.set( propertyId, statementSection );
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

const getIndicatorHtmlForMainSnak = function ( statementId ) {
	const statementsStore = useSavedStatementsStore();
	const snakHash = ( statementsStore.statements.get( statementId ) || { mainsnak: {} } ).mainsnak.hash;
	if ( snakHash && snakValueHtmlForHashHasError( snakHash ) ) {
		return '<span class="wikibase-wbui2025-indicator-icon--error"></span>';
	}
	return statementsStore.indicatorHtmlForMainSnaks.get( statementId );
};

const setIndicatorHtmlForMainSnak = function ( statementId, indicatorHtml ) {
	const statementsStore = useSavedStatementsStore();
	statementsStore.indicatorHtmlForMainSnaks.set( statementId, indicatorHtml );
};

const getIndicatorHtmlForQualifier = function ( statementId, snakHash ) {
	if ( snakValueHtmlForHashHasError( snakHash ) ) {
		return '<span class="wikibase-wbui2025-indicator-icon--error"></span>';
	}
	const statementsStore = useSavedStatementsStore();
	return statementsStore.indicatorHtmlForQualifiers.get( `${ statementId }|${ snakHash }` );
};

const setIndicatorHtmlForQualifier = function ( statementId, snakHash, indicatorHtml ) {
	const statementsStore = useSavedStatementsStore();
	statementsStore.indicatorHtmlForQualifiers.set( `${ statementId }|${ snakHash }`, indicatorHtml );
};

const getIndicatorHtmlForReferenceSnak = function ( statementId, referenceHash, snakHash ) {
	if ( snakValueHtmlForHashHasError( snakHash ) ) {
		return '<span class="wikibase-wbui2025-indicator-icon--error"></span>';
	}
	const statementsStore = useSavedStatementsStore();
	return statementsStore.indicatorHtmlForReferenceSnaks.get( `${ statementId }|${ referenceHash }|${ snakHash }` );
};

const setIndicatorHtmlForReferenceSnak = function ( statementId, referenceHash, snakHash, indicatorHtml ) {
	const statementsStore = useSavedStatementsStore();
	statementsStore.indicatorHtmlForReferenceSnaks.set( `${ statementId }|${ referenceHash }|${ snakHash }`, indicatorHtml );
};

const getPopoverContentForMainSnak = function ( statementId ) {
	const statementsStore = useSavedStatementsStore();
	const popoverContentItems = statementsStore.popoverHtmlForMainSnaks.get( statementId ) || [];
	const snakHash = ( statementsStore.statements.get( statementId ) || { mainsnak: {} } ).mainsnak.hash;
	if ( snakValueHtmlForHashHasError( snakHash ) ) {
		return [
			{
				bodyHtml: snakValueHtmlForHash( snakHash )
			},
			...popoverContentItems
		];
	} else {
		return popoverContentItems;
	}
};

const setPopoverContentForMainSnak = function ( statementId, popoverContentItems ) {
	const statementsStore = useSavedStatementsStore();
	statementsStore.popoverHtmlForMainSnaks.set( statementId, popoverContentItems );
};

const getPopoverContentForQualifier = function ( statementId, snakHash ) {
	const statementsStore = useSavedStatementsStore();
	const popoverContentItems = statementsStore.popoverHtmlForQualifiers.get( `${ statementId }|${ snakHash }` ) || [];
	if ( snakValueHtmlForHashHasError( snakHash ) ) {
		return [
			{
				bodyHtml: snakValueHtmlForHash( snakHash )
			},
			...popoverContentItems
		];
	} else {
		return popoverContentItems;
	}
};

const setPopoverContentForQualifier = function ( statementId, snakHash, popoverContentItems ) {
	const statementsStore = useSavedStatementsStore();
	statementsStore.popoverHtmlForQualifiers.set( `${ statementId }|${ snakHash }`, popoverContentItems );
};

const getPopoverContentForReferenceSnak = function ( statementId, referenceHash, snakHash ) {
	const statementsStore = useSavedStatementsStore();
	const popoverContentItems = statementsStore.popoverHtmlForReferenceSnaks.get( `${ statementId }|${ referenceHash }|${ snakHash }` ) || [];
	if ( snakValueHtmlForHashHasError( snakHash ) ) {
		return [
			{
				bodyHtml: snakValueHtmlForHash( snakHash )
			},
			...popoverContentItems
		];
	} else {
		return popoverContentItems;
	}
};

const setPopoverContentForReferenceSnak = function ( statementId, referenceHash, snakHash, popoverContentItems ) {
	const statementsStore = useSavedStatementsStore();
	statementsStore.popoverHtmlForReferenceSnaks.set( `${ statementId }|${ referenceHash }|${ snakHash }`, popoverContentItems );
};

module.exports = {
	useSavedStatementsStore,
	getPropertyIds,
	getPropertyIdsForStatementSection,
	setStatementSectionForPropertyId,
	getStatementsForProperty,
	getStatementById,
	setStatementIdsForProperty,
	getIndicatorHtmlForMainSnak,
	setIndicatorHtmlForMainSnak,
	getIndicatorHtmlForQualifier,
	setIndicatorHtmlForQualifier,
	getIndicatorHtmlForReferenceSnak,
	setIndicatorHtmlForReferenceSnak,
	setPopoverContentForMainSnak,
	getPopoverContentForMainSnak,
	setPopoverContentForQualifier,
	getPopoverContentForQualifier,
	setPopoverContentForReferenceSnak,
	getPopoverContentForReferenceSnak
};
