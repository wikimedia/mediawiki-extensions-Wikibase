/**
 * @license GPL-2.0-or-later
 */
const wbui2025 = {};
wbui2025.api = Object.assign(
	require( './api/editEntity.js' ),
	require( './api/api.js' ),
	require( './api/commons.js' )
);
wbui2025.util = require( './wikibase.wbui2025.utils.js' );
wbui2025.store = Object.assign(
	require( './store/snakValueStrategies.js' ),
	require( './store/snakValueStrategyFactory.js' ),
	require( './store/editStatementsStore.js' ),
	require( './store/messageStore.js' ),
	require( './store/savedStatementsStore.js' ),
	require( './store/serverRenderedHtml.js' ),
	require( './store/parsedValueStore.js' )
);

module.exports = wbui2025;
