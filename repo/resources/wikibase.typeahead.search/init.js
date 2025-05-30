const searchClient = require( './searchClient.js' );
const skin = mw.config.get( 'skin' );
const moduleName = skin === 'vector-2022' ? 'skins.vector.search' : 'skins.minerva.search';

function initSearchClient() {
	mw.loader.using( moduleName ).then( () => {
		// eslint-disable-next-line security/detect-non-literal-require
		const { init } = require( moduleName );
		init( searchClient );
	} );
}

module.exports = {
	init: initSearchClient
};
