const { defineStore } = require( 'pinia' );
const { useStatementsStore } = require( './statementsStore.js' );
const { updateStatements } = require( '../api/editEntity.js' );

const useEditStatementStore = ( statementId ) => defineStore( 'editStatement-' + statementId, {
	state: () => ( {
		rank: 'normal',
		snaktype: 'value',
		value: '',
		qualifiers: {},
		qualifiersOrder: [],
		references: [],
		propertyId: undefined
	} ),
	actions: {
		/**
		 * @param {Object|undefined} statementData
		 * @param {string} propertyId
		 */
		initializeWithStatement( statementData, propertyId ) {
			this.$reset();
			this.propertyId = propertyId;
			if ( statementData === undefined ) {
				return;
			}
			this.rank = statementData.rank;
			this.snaktype = statementData.mainsnak.snaktype;
			if ( statementData.mainsnak.snaktype === 'value' ) {
				this.value = statementData.mainsnak.datavalue.value;
			}
			this.qualifiers = {};
			for ( const [ qualifierPropertyId, statementList ] of Object.entries( statementData.qualifiers || {} ) ) {
				this.qualifiers[ qualifierPropertyId ] = statementList.slice( 0 );
			}
			this.qualifiersOrder = ( statementData[ 'qualifiers-order' ] || [] ).slice( 0 );
			this.references = ( statementData.references || [] ).slice( 0 );
		}
	}
} );

const buildStatementObjectFromMutableStatement = function ( statementId ) {
	const editStatementStore = useEditStatementStore( statementId )();
	const builtData = {
		id: statementId,
		mainsnak: {
			snaktype: editStatementStore.snaktype,
			property: editStatementStore.propertyId
		},
		references: editStatementStore.references,
		'qualifiers-order': editStatementStore.qualifiersOrder,
		qualifiers: editStatementStore.qualifiers,
		type: 'statement',
		rank: editStatementStore.rank
	};
	if ( editStatementStore.snaktype === 'value' ) {
		builtData.mainsnak.datavalue = {
			value: editStatementStore.value,
			type: 'string'
		};
		builtData.mainsnak.datatype = 'string';
	}
	return builtData;
};

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
		 */
		appendStatementToEditStatementsStore( statementId, propertyId ) {
			this.statements.push( statementId );
			const statementsStore = useStatementsStore();
			const editStatementStore = useEditStatementStore( statementId )();
			editStatementStore.initializeWithStatement( statementsStore.statements.get( statementId ), propertyId );
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
		 */
		createNewBlankStatement( statementId, propertyId ) {
			this.createdStatements.push( statementId );
			this.appendStatementToEditStatementsStore( statementId, propertyId );
		},
		/**
		 * @param {string} removeStatementId
		 */
		removeStatement( removeStatementId ) {
			this.statements = this.statements.filter( ( statementId ) => statementId !== removeStatementId );
			this.removedStatements.push( removeStatementId );
		},

		/**
		 * @param {string} entityId
		 */
		saveChangedStatements( entityId ) {
			const statementsStore = useStatementsStore();
			return updateStatements( entityId, this.statementsForSerialization )
				.then( ( returnedClaims ) => statementsStore.populateWithClaims( returnedClaims, true ) );
		}
	},
	getters: {
		statementIds: ( state ) => state.statements,
		statementsForSerialization( state ) {
			const claimsToDeleteOnSubmit = state.removedStatements
				.filter( ( statementId ) => !state.createdStatements.includes( statementId ) )
				.map( ( statementId ) => ( { id: statementId, remove: '' } ) );
			return state.statements
				.map( ( statementId ) => buildStatementObjectFromMutableStatement( statementId ) )
				.concat( claimsToDeleteOnSubmit );
		}
	}
} );

module.exports = {
	useEditStatementStore,
	useEditStatementsStore
};
