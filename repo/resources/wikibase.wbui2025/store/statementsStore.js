const { defineStore } = require( 'pinia' );

const useStatementsStore = defineStore( 'statements', {
	state: () => ( {
		statements: new Map(),
		properties: new Map()
	} ),
	actions: {
		populateWithClaims( claims ) {
			const unfrozenClaims = JSON.parse( JSON.stringify( claims ) );
			for ( const [ propertyId, statementList ] of Object.entries( unfrozenClaims ) ) {
				const statementIdList = [];
				for ( const statement of statementList ) {
					this.statements.set( statement.id, statement );
					statementIdList.push( statement.id );
				}
				this.properties.set( propertyId, statementIdList );
			}
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

const updateStatementData = function ( statementId, statementData ) {
	const statementsStore = useStatementsStore();
	statementsStore.statements.set( statementId, statementData );
};

const removeStatementData = function ( propertyId, statementId ) {
	const statementsStore = useStatementsStore();
	statementsStore.statements.delete( statementId );
	const statementsForProperty = statementsStore.properties.get( propertyId );
	statementsStore.properties.set(
		propertyId,
		statementsForProperty.filter( ( existingStatementId ) => existingStatementId !== statementId )
	);
};

const setStatementIdsForProperty = function ( propertyId, statementIds ) {
	const statementsStore = useStatementsStore();
	statementsStore.properties.set( propertyId, statementIds );
};

module.exports = {
	useStatementsStore,
	removeStatementData,
	getPropertyIds,
	getStatementsForProperty,
	getStatementById,
	setStatementIdsForProperty,
	updateStatementData
};
