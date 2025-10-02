const { defineStore, getActivePinia } = require( 'pinia' );
const { useParsedValueStore } = require( './parsedValueStore.js' );
const { useSavedStatementsStore, getStatementById } = require( './savedStatementsStore.js' );
const { updateStatements } = require( '../api/editEntity.js' );

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
		// TODO add cases for other data value types as we implement them (T403974, T407324)
		default:
			throw new Error( `Unsupported data value type ${ dv1.type }` );
	}
}

const useEditSnakStore = ( snakKey ) => defineStore( 'editSnak-' + snakKey, {
	state: () => ( {
		value: undefined,
		snaktype: 'value',
		property: undefined,
		datatype: 'string',
		valuetype: 'string',
		hash: undefined
	} ),
	actions: {
		initializeWithSnak( snak ) {
			this.snaktype = snak.snaktype;
			if ( this.snaktype === 'value' ) {
				this.value = snak.datavalue.value;
				this.valuetype = snak.datavalue.type;
			}
			this.datatype = snak.datatype;
			this.property = snak.property;
			this.hash = snak.hash;
		},
		async buildSnakJson() {
			const parsedValueStore = useParsedValueStore();
			const snakJson = {
				snaktype: this.snaktype,
				property: this.property,
				datatype: this.datatype
			};
			if ( this.snaktype === 'value' ) {
				snakJson.datavalue = await parsedValueStore.getParsedValue(
					this.property,
					this.value
				);
				snakJson.datatype = 'string';
			}
			return snakJson;
		},
		currentDataValue() {
			if ( this.snaktype !== 'value' ) {
				return undefined;
			}
			return {
				type: this.valuetype,
				value: this.value
			};
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
		initializeWithStatement( statementData, propertyId, propertyDataType = null ) {
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
			useEditSnakStore( this.mainSnakKey )().initializeWithSnak( statementData.mainsnak );
			this.qualifiers = {};
			for ( const [ qualifierPropertyId, statementList ] of Object.entries( statementData.qualifiers || {} ) ) {
				this.qualifiers[ qualifierPropertyId ] = statementList.map( ( snak ) => {
					const snakKey = generateNextSnakKey();
					useEditSnakStore( snakKey )().initializeWithSnak( snak );
					return snakKey;
				} );
			}
			this.qualifiersOrder = ( statementData[ 'qualifiers-order' ] || [] ).slice( 0 );
			this.references = ( statementData.references || [] ).slice( 0 );
		},

		disposeOfStatementStoreAndSnaks() {
			deleteStore( useEditSnakStore( this.mainSnakKey )() );
			for ( const [ , statementList ] of Object.entries( this.qualifiers ) ) {
				statementList.forEach( ( snakKey ) => deleteStore( useEditSnakStore( snakKey )() ) );
			}
			// TODO: T405236 Also dispose of reference snak data here
			deleteStore( this );
		}
	},
	getters: {
		isFullyParsed( state ) {
			const parsedValueStore = useParsedValueStore();
			const isFullyParsedValue = ( value ) => value !== undefined && value !== null;
			const snakFullyParsed = function ( snak ) {
				return snak.snaktype !== 'value' ||
					isFullyParsedValue( parsedValueStore.peekParsedValue( snak.property, snak.value ) );
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
			// TODO check references once they use wbparsevalue (T406887)
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
				const dataValue = useParsedValueStore().peekParsedValue( state.propertyId, mainSnakStore.value );
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
					if ( qualifier.snaktype === 'value' && !sameDataValue( qualifier.currentDataValue(), savedQualifier.datavalue ) ) {
						return true;
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
						const referenceSnak = referenceSnaks[ j ];
						const savedReferenceSnak = savedReferenceSnaks[ j ];
						if ( referenceSnak.snaktype !== savedReferenceSnak.snaktype ) {
							return true;
						}
						if ( referenceSnak.snaktype === 'value' && !sameDataValue( referenceSnak.datavalue, savedReferenceSnak.datavalue ) ) {
							return true;
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
		appendStatementToEditStatementsStore( statementId, propertyId, propertyDataType = null ) {
			this.statements.push( statementId );
			const statementsStore = useSavedStatementsStore();
			const editStatementStore = useEditStatementStore( statementId )();
			editStatementStore.initializeWithStatement( statementsStore.statements.get( statementId ), propertyId, propertyDataType );
		},
		/**
		 * @param {Array<string>} statementIds
		 * @param {string} propertyId
		 */
		initializeFromStatementStore( statementIds, propertyId ) {
			this.$reset();
			this.statements = [];
			statementIds.forEach( ( statementId ) => this.appendStatementToEditStatementsStore( statementId, propertyId ) );
		},
		/**
		 * @param {string} statementId
		 * @param {string} propertyId
		 * @param {string} propertyDataType
		 */
		createNewBlankStatement( statementId, propertyId, propertyDataType ) {
			this.createdStatements.push( statementId );
			this.appendStatementToEditStatementsStore( statementId, propertyId, propertyDataType );
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
			return {
				id: statementId,
				mainsnak: await mainSnakStore.buildSnakJson(),
				references: editStatementStore.references,
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
