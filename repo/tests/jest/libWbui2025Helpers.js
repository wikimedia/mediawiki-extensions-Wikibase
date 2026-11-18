const mockLibWbui2025 = function () {
	jest.mock(
		'../../resources/wikibase.wbui2025/repoSettings.json',
		() => ( {
			tabularDataStorageApiEndpointUrl: '',
			geoShapeStorageApiEndpointUrl: ''
		} ),
		{ virtual: true }
	);

	jest.mock(
		'wikibase.wbui2025.lib',
		() => require( '../../resources/wikibase.wbui2025/lib.js' ),
		{ virtual: true }
	);

	jest.mock(
		'wikibase.copyright',
		() => require( '../../../view/resources/wikibase/copyright/copyright.js' ),
		{ virtual: true }
	);

};

const mockConfig = function ( configMap ) {
	mw.config = {
		exists: ( key ) => key in configMap,
		get: ( key ) => configMap[ key ]
	};
};

module.exports = {
	mockLibWbui2025,
	mockConfig
};
