const { createTestingPinia } = require( '@pinia/testing' );

const createStatementsMap = function ( statements ) {
	return new Map( statements.map( ( statement ) => [ statement.id, statement ] ) );
};

const storeWithStatements = function ( statements ) {
	return createTestingPinia(
		{
			initialState: {
				statements: {
					statements: createStatementsMap( statements )
				}
			}
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
		statements: {
			statements: statementMap,
			properties: propertyMap
		}
	};
};

const storeWithStatementsAndProperties = function ( propertyStatementMap ) {
	return createTestingPinia( {
		initialState: storeContentWithStatementsAndProperties( propertyStatementMap )
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
		initialState: storeContentsWithServerRenderedHtml( snakHashToHtmlMap, propertyToHtmlMap )
	} );
};

const storeWithHtmlAndStatements = function ( serverRenderedHtmlStoreContents, statementsStoreContents ) {
	return createTestingPinia( {
		initialState: Object.assign( {}, serverRenderedHtmlStoreContents, statementsStoreContents )
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
