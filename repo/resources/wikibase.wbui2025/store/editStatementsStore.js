const { defineStore, getActivePinia } = require( 'pinia' );
const { useSavedStatementsStore, getStatementById } = require( './savedStatementsStore.js' );
const { snakValueStrategyFactory } = require( './snakValueStrategyFactory.js' );
require( './snakValueStrategies.js' );
const { updateStatements, renderSnakValueHtml, renderPropertyLinkHtml } = require( '../api/editEntity.js' );
const { updateSnakValueHtmlForHash, updatePropertyLinkHtml } = require( './serverRenderedHtml.js' );

/**
 * Check if two data values (objects with "type" and "value" keys) are equal.
 *
 * @param {Object} dv1
 * @param {Object} dv2
 * @return {boolean}
 */
function sameDataValue( dv1, dv2 ) {
	if ( dv1.type !== dv2.type ) {
		return false;
	}
	switch ( dv1.type ) {
		case 'string':
			// the value is directly a string
			return dv1.value === dv2.value;
		case 'wikibase-entityid':
			return dv1.value.id === dv2.value.id && dv1.value[ 'entity-type' ] === dv2.value[ 'entity-type' ];
		// TODO add cases for other data value types as we implement them (T407324)
		default:
			throw new Error( `Unsupported data value type ${ dv1.type }` );
	}
}

const useEditSnakStore = ( snakKey ) => defineStore( 'editSnak-' + snakKey, {
	state: () => ( {
		value: undefined,
		textvalue: undefined,
		selectionvalue: undefined,
		snaktype: 'value',
		property: undefined,
		datatype: 'string',
		valuetype: 'string',
		hash: undefined
	} ),
	actions: {
		getValueStrategy() {
			return snakValueStrategyFactory.getStrategyForSnakStore( this );
		},
		async initializeWithSnak( snak ) {
			this.snaktype = snak.snaktype;
			this.datatype = snak.datatype;
			this.property = snak.property;
			if ( this.snaktype === 'value' ) {
				this.textvalue = await this.getValueStrategy().renderValueToText( snak.datavalue );
				this.selectionvalue = this.getValueStrategy().getSelectionValueForSavedValue( snak.datavalue );
				this.value = snak.datavalue.value;
				this.valuetype = snak.datavalue.type;
			}
			this.hash = snak.hash;
		},
		async buildSnakJson() {
			const snakJson = {
				snaktype: this.snaktype,
				property: this.property,
				datatype: this.datatype
			};
			if ( this.snaktype === 'value' ) {
				snakJson.datavalue = await this.getValueStrategy().buildDataValue( this );
			}
			return snakJson;
		},
		currentDataValue() {
			if ( this.snaktype !== 'value' ) {
				return undefined;
			}
			return this.getValueStrategy().peekDataValue( this );
		},
		dispose() {
			deleteStore( this );
		}
	}
} );

let nextSnakKey = 0;

const generateNextSnakKey = function () {
	return 'snak' + nextSnakKey++;
};

function deleteStore( store ) {
	delete getActivePinia().state.value[ store.$id ];
	store.$dispose();
}

const useEditStatementStore = ( statementId ) => defineStore( 'editStatement-' + statementId, {
	state: () => ( {
		rank: 'normal',
		mainSnakKey: undefined,
		qualifiers: {},
		qualifiersOrder: [],
		references: [],
		propertyId: undefined
	} ),
	actions: {
		/**
		 * @param {Object|undefined} statementData
		 * @param {string} propertyId
		 * @param {string|null} propertyDataType
		 */
		async initializeWithStatement( statementData, propertyId, propertyDataType = null ) {
			this.$reset();
			this.propertyId = propertyId;
			if ( statementData === undefined ) {
				statementData = {
					rank: 'normal',
					propertyId,
					mainsnak: {
						property: propertyId,
						snaktype: 'value',
						datatype: propertyDataType || 'string',
						datavalue: {
							value: '',
							type: 'string'
						}
					}
				};
			}
			this.rank = statementData.rank;
			this.mainSnakKey = generateNextSnakKey();
			await useEditSnakStore( this.mainSnakKey )().initializeWithSnak( statementData.mainsnak );
			this.qualifiers = {};
			const snakInitPromises = [];
			for ( const [ qualifierPropertyId, statementList ] of Object.entries( statementData.qualifiers || {} ) ) {
				this.qualifiers[ qualifierPropertyId ] = statementList.map( ( snak ) => {
					const snakKey = generateNextSnakKey();
					snakInitPromises.push( useEditSnakStore( snakKey )().initializeWithSnak( snak ) );
					return snakKey;
				} );
			}
			this.qualifiersOrder = ( statementData[ 'qualifiers-order' ] || [] ).slice( 0 );
			this.references = ( statementData.references || [] ).map( ( reference ) => {
				const tempReference = {
					hash: reference.hash,
					snaks: {},
					'snaks-order': ( reference[ 'snaks-order' ] || [] ).slice( 0 )
				};
				for ( const [ snakPropertyId, snakList ] of Object.entries( reference.snaks || {} ) ) {
					tempReference.snaks[ snakPropertyId ] = snakList.map( ( snak ) => {
						const snakKey = generateNextSnakKey();
						snakInitPromises.push( useEditSnakStore( snakKey )().initializeWithSnak( snak ) );
						return snakKey;
					} );
				}
				return tempReference;
			} );
			await Promise.all( snakInitPromises );
		},
		async addQualifier( snakKey ) {
			const editSnakStore = useEditSnakStore( snakKey )();

			if ( !this.qualifiers[ editSnakStore.property ] ) {
				this.qualifiers[ editSnakStore.property ] = [];
				this.qualifiersOrder.push( editSnakStore.property );
				renderPropertyLinkHtml( editSnakStore.property ).then( ( result ) => updatePropertyLinkHtml( editSnakStore.property, result ) );
			}
			this.qualifiers[ editSnakStore.property ].push( snakKey );

			renderSnakValueHtml( editSnakStore.currentDataValue(), editSnakStore.property ).then( ( result ) => updateSnakValueHtmlForHash( snakKey, result ) );

			if ( editSnakStore.snaktype === 'value' ) {
				editSnakStore.getValueStrategy().getParsedValue();
			}
		},
		async addReference( snakKey ) {
			const editSnakStore = useEditSnakStore( snakKey )();

			renderPropertyLinkHtml( editSnakStore.property ).then( ( result ) => updatePropertyLinkHtml( editSnakStore.property, result ) );
			renderSnakValueHtml( editSnakStore.currentDataValue(), editSnakStore.property )
				.then( ( result ) => updateSnakValueHtmlForHash( snakKey, result ) );

			const newReference = {
				hash: snakKey,
				snaks: { [ editSnakStore.property ]: [ snakKey ] },
				'snaks-order': [ editSnakStore.property ]
			};
			this.references.push( newReference );

			if ( editSnakStore.valuetype === 'value' ) {
				editSnakStore.getValueStrategy().getParsedValue();
			}
		},
		disposeOfStatementStoreAndSnaks() {
			useEditSnakStore( this.mainSnakKey )().dispose();
			for ( const [ , statementList ] of Object.entries( this.qualifiers ) ) {
				statementList.forEach( ( snakKey ) => useEditSnakStore( snakKey )().dispose() );
			}
			for ( const reference of this.references ) {
				for ( const [ , statementList ] of Object.entries( reference.snaks || {} ) ) {
					statementList.forEach( ( snakKey ) => useEditSnakStore( snakKey )().dispose() );
				}
			}
			deleteStore( this );
		}
	},
	getters: {
		isFullyParsed( state ) {
			const isFullyParsedValue = ( value ) => value !== undefined && value !== null;
			const snakFullyParsed = function ( snak ) {
				return snak.snaktype !== 'value' ||
					isFullyParsedValue( snak.getValueStrategy().peekDataValue( snak ) );
			};
			const mainSnakState = useEditSnakStore( state.mainSnakKey )();
			if ( !snakFullyParsed( mainSnakState ) ) {
				return false;
			}
			for ( const propertyId of state.qualifiersOrder ) {
				for ( const snakKey of state.qualifiers[ propertyId ] ) {
					const qualifierSnakState = useEditSnakStore( snakKey )();
					if ( !snakFullyParsed( qualifierSnakState ) ) {
						return false;
					}
				}
			}
			for ( const reference of state.references ) {
				for ( const propertyId of reference[ 'snaks-order' ] ) {
					if ( !Array.isArray( ( reference.snaks || {} )[ propertyId ] ) ) {
						continue;
					}
					for ( const snakKey of reference.snaks[ propertyId ] ) {
						const referenceSnakState = useEditSnakStore( snakKey )();
						if ( !snakFullyParsed( referenceSnakState ) ) {
							return false;
						}
					}
				}
			}
			return true;
		},
		/**
		 * @return {boolean|null} True or false if the statement is known to be different or the same
		 * as the saved version in the savedStatementsStore, or null if it is not known
		 * (because not all values are fully parsed yet or any of them could not be parsed).
		 * If parts of the statement are known to be different and others are not fully parsed yet,
		 * it is not defined whether null or true will be returned.
		 */
		hasChanges( state ) {
			const savedStatement = getStatementById( statementId );

			if ( !savedStatement ) {
				return true;
			}

			const mainSnakStore = useEditSnakStore( state.mainSnakKey )();
			if ( state.rank !== savedStatement.rank || mainSnakStore.snaktype !== savedStatement.mainsnak.snaktype ) {
				return true;
			}

			if ( mainSnakStore.snaktype === 'value' ) {
				const dataValue = mainSnakStore.currentDataValue();
				if ( !dataValue ) {
					return null;
				}
				if ( !sameDataValue( dataValue, savedStatement.mainsnak.datavalue ) ) {
					return true;
				}
			}

			const qualifierPropertyIds = new Set( [ ...state.qualifiersOrder, ...( savedStatement[ 'qualifiers-order' ] || [] ) ] );
			for ( const qualifierPropertyId of qualifierPropertyIds ) {
				const qualifiers = state.qualifiers[ qualifierPropertyId ];
				const savedQualifiers = ( savedStatement.qualifiers || {} )[ qualifierPropertyId ];
				if ( !qualifiers || !savedQualifiers ) {
					return true;
				}
				if ( qualifiers.length !== savedQualifiers.length ) {
					return true;
				}
				for ( let i = 0; i < qualifiers.length; i++ ) {
					const qualifier = useEditSnakStore( qualifiers[ i ] )();
					const savedQualifier = savedQualifiers[ i ];
					if ( qualifier.snaktype !== savedQualifier.snaktype ) {
						return true;
					}
					if ( qualifier.snaktype === 'value' ) {
						const qualifierDataValue = qualifier.currentDataValue();
						if ( !qualifierDataValue ) {
							return null;
						}
						if ( !sameDataValue( qualifierDataValue, savedQualifier.datavalue ) ) {
							return true;
						}
					}
				}
			}

			if ( state.references.length !== ( savedStatement.references || [] ).length ) {
				return true;
			}
			for ( let i = 0; i < state.references.length; i++ ) {
				const reference = state.references[ i ];
				const savedReference = savedStatement.references[ i ];
				if ( reference.hash !== savedReference.hash ) {
					return true;
				}
				const referencePropertyIds = new Set( [ ...reference[ 'snaks-order' ], ...savedReference[ 'snaks-order' ] ] );
				for ( const referencePropertyId of referencePropertyIds ) {
					const referenceSnaks = reference.snaks[ referencePropertyId ];
					const savedReferenceSnaks = savedReference.snaks[ referencePropertyId ];
					if ( !referenceSnaks || !savedReferenceSnaks ) {
						return true;
					}
					if ( referenceSnaks.length !== savedReferenceSnaks.length ) {
						return true;
					}
					for ( let j = 0; j < referenceSnaks.length; j++ ) {
						const referenceSnak = useEditSnakStore( referenceSnaks[ j ] )();
						const savedReferenceSnak = savedReferenceSnaks[ j ];
						if ( referenceSnak.snaktype !== savedReferenceSnak.snaktype ) {
							return true;
						}
						if ( referenceSnak.snaktype === 'value' ) {
							const referenceDataValue = referenceSnak.currentDataValue();
							if ( !referenceDataValue ) {
								return null;
							}
							if ( referenceSnak.snaktype === 'value' && !sameDataValue( referenceDataValue, savedReferenceSnak.datavalue ) ) {
								return true;
							}
						}
					}
				}
			}

			return false;
		}
	}
} );

const useEditStatementsStore = defineStore( 'editStatements', {
	state: () => ( {
		statements: [],
		createdStatements: [],
		removedStatements: []
	} ),
	actions: {
		/**
		 * @param {string} statementId
		 * @param {string} propertyId
		 * @param {string|null} propertyDataType
		 */
		async appendStatementToEditStatementsStore( statementId, propertyId, propertyDataType = null ) {
			this.statements.push( statementId );
			const statementsStore = useSavedStatementsStore();
			const editStatementStore = useEditStatementStore( statementId )();
			return editStatementStore.initializeWithStatement( statementsStore.statements.get( statementId ), propertyId, propertyDataType );
		},
		/**
		 * @param {Array<string>} statementIds
		 * @param {string} propertyId
		 */
		async initializeFromStatementStore( statementIds, propertyId ) {
			this.$reset();
			this.statements = [];
			return Promise.all( statementIds.map( ( statementId ) => this.appendStatementToEditStatementsStore( statementId, propertyId ) ) );
		},
		/**
		 * @param {string} statementId
		 * @param {string} propertyId
		 * @param {string} propertyDataType
		 */
		async createNewBlankStatement( statementId, propertyId, propertyDataType ) {
			this.createdStatements.push( statementId );
			return this.appendStatementToEditStatementsStore( statementId, propertyId, propertyDataType );
		},
		/**
		 * @param {string} removeStatementId
		 */
		removeStatement( removeStatementId ) {
			this.statements = this.statements.filter( ( statementId ) => statementId !== removeStatementId );
			this.removedStatements.push( removeStatementId );
		},

		/**
		 * @private
		 * @param {string} statementId
		 * @returns {Promise<object>}
		 */
		async buildStatementObjectFromMutableStatement( statementId ) {
			const editStatementStore = useEditStatementStore( statementId )();
			const mainSnakStore = useEditSnakStore( editStatementStore.mainSnakKey )();
			const builtQualifierData = {};
			for ( const [ propertyId, statementList ] of Object.entries( editStatementStore.qualifiers ) ) {
				builtQualifierData[ propertyId ] = await Promise.all( statementList.map(
					async ( snakHash ) => await useEditSnakStore( snakHash )().buildSnakJson()
				) );
			}

			const builtReferencesData = await Promise.all( editStatementStore.references.map( async ( reference ) => {
				const builtReference = {
					'snaks-order': reference[ 'snaks-order' ],
					snaks: {}
				};
				for ( const [ propertyId, statementList ] of Object.entries( reference.snaks ) ) {
					builtReference.snaks[ propertyId ] = await Promise.all( statementList.map(
						async ( snakHash ) => await useEditSnakStore( snakHash )().buildSnakJson()
					) );
				}
				return builtReference;
			} ) );
			return {
				id: statementId,
				mainsnak: await mainSnakStore.buildSnakJson(),
				references: builtReferencesData,
				'qualifiers-order': editStatementStore.qualifiersOrder,
				qualifiers: builtQualifierData,
				type: 'statement',
				rank: editStatementStore.rank
			};
		},

		/**
		 * @private
		 * @returns {Promise<object[]>}
		 */
		async buildStatementsForSerialization() {
			const claimsToDeleteOnSubmit = this.removedStatements
				.filter( ( statementId ) => !this.createdStatements.includes( statementId ) )
				.map( ( statementId ) => ( { id: statementId, remove: '' } ) );
			const statements = [];
			for ( const statementId of this.statements ) {
				statements.push( await this.buildStatementObjectFromMutableStatement( statementId ) );
			}
			return statements.concat( claimsToDeleteOnSubmit );
		},

		/**
		 * @param {string} entityId
		 */
		async saveChangedStatements( entityId ) {
			const statementsStore = useSavedStatementsStore();
			return updateStatements( entityId, await this.buildStatementsForSerialization() )
				.then( ( returnedClaims ) => statementsStore.populateWithClaims( returnedClaims, true ) );
		},

		disposeOfStores() {
			this.statements.forEach( ( statementId ) => useEditStatementStore( statementId )().disposeOfStatementStoreAndSnaks() );
		}
	},
	getters: {
		statementIds: ( state ) => state.statements,
		isFullyParsed( state ) {
			for ( const statementId of state.statements ) {
				const editStatementStore = useEditStatementStore( statementId )();
				if ( !editStatementStore.isFullyParsed ) {
					return false;
				}
			}
			return true;
		},
		createdStatementIds: ( state ) => state.createdStatements,
		/**
		 * @return {boolean|null} True or false if the statements are known to be different or the same
		 * as the saved version in the savedStatementsStore, or null if it is not known
		 * (because not all values are fully parsed yet or any of them could not be parsed).
		 * If parts of the statements are known to be different and others are not fully parsed yet,
		 * it is not defined whether null or true will be returned.
		 */
		hasChanges( state ) {
			if ( state.createdStatements.length > 0 || state.removedStatements.length > 0 ) {
				return true;
			}
			for ( const statementId of state.statements ) {
				const editStatementStore = useEditStatementStore( statementId )();
				if ( editStatementStore.hasChanges !== false ) {
					return editStatementStore.hasChanges;
				}
			}
			return false;
		}
	}
} );

module.exports = {
	useEditSnakStore,
	useEditStatementStore,
	useEditStatementsStore,
	generateNextSnakKey
};
