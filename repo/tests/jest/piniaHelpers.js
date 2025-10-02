const { createTestingPinia } = require( '@pinia/testing' );

const createStatementsMap = function ( statements ) {
	return new Map( statements.map( ( statement ) => [ statement.id, statement ] ) );
};

const storeWithStatements = function ( statements ) {
	return createTestingPinia(
		{
			initialState: {
				savedStatements: {
					statements: createStatementsMap( statements )
				}
			},
			stubActions: false
		}
	);
};

const storeContentWithStatementsAndProperties = function ( propertyStatementMap ) {
	const statementMap = new Map();
	const propertyMap = new Map( Object.entries( propertyStatementMap ).map( ( [ propertyId, statementList ] ) => {
		statementList.forEach( ( statement ) => {
			statementMap.set( statement.id, statement );
		} );
		return [ propertyId, statementList.map( ( statement ) => statement.id ) ];
	} ) );
	return {
		savedStatements: {
			statements: statementMap,
			properties: propertyMap
		}
	};
};

const storeWithStatementsAndProperties = function ( propertyStatementMap ) {
	return createTestingPinia( {
		initialState: storeContentWithStatementsAndProperties( propertyStatementMap ),
		stubActions: false
	} );
};

const storeContentsWithServerRenderedHtml = function ( snakHashToHtmlMap, propertyToHtmlMap = {} ) {
	return {
		serverRenderedHtml: {
			snakValues: new Map( Object.entries( snakHashToHtmlMap ) ),
			propertyLinks: new Map( Object.entries( propertyToHtmlMap ) )
		}
	};
};

const storeWithServerRenderedHtml = function ( snakHashToHtmlMap, propertyToHtmlMap = {} ) {
	return createTestingPinia( {
		initialState: storeContentsWithServerRenderedHtml( snakHashToHtmlMap, propertyToHtmlMap ),
		stubActions: false
	} );
};

const storeWithHtmlAndStatements = function ( serverRenderedHtmlStoreContents, statementsStoreContents ) {
	return createTestingPinia( {
		initialState: Object.assign( {}, serverRenderedHtmlStoreContents, statementsStoreContents ),
		stubActions: false
	} );
};

module.exports = {
	storeWithStatements,
	storeWithStatementsAndProperties,
	createStatementsMap,
	storeWithServerRenderedHtml,
	storeWithHtmlAndStatements,
	storeContentsWithServerRenderedHtml,
	storeContentWithStatementsAndProperties
};
