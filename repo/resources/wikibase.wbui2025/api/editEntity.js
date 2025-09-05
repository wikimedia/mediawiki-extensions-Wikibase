const { updateStatementData } = require( '../store/statementsStore.js' );
const api = new mw.Api();

/**
 * @param {string} itemId The ID of the item to update
 * @param {string} statementId The ID of the statement to update
 * @param {Object} mainSnak The value of the snak
 */
const updateMainSnak = async function ( itemId, statementId, mainSnak ) {
	const data = {
		claims: [
			{
				id: statementId,
				mainsnak: mainSnak,
				type: 'statement',
				rank: 'normal'
			}
		]
	};

	return api.postWithEditToken( {
		action: 'wbeditentity',
		id: itemId,
		data: JSON.stringify( data )
	// TODO: Update the statement data store with the data returned from the server T401405
	} ).then( () => updateStatementData( statementId, data.claims[ 0 ] ) );
};

/**
 * @param {Object} dataValue
 */
const renderSnakValueHtml = async function ( dataValue ) {
	const fetchResult = await api.get( {
		action: 'wbformatvalue',
		generate: 'text/html; disposition=verbose-preview',
		datavalue: JSON.stringify( dataValue )
	} );
	if ( !fetchResult.result.startsWith( '<' ) ) {
		return '<p>' + fetchResult.result + '</p>';
	}
	return fetchResult.result;
};

module.exports = {
	updateMainSnak,
	renderSnakValueHtml
};
