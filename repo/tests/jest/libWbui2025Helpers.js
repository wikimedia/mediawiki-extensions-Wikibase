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
};

module.exports = {
	mockLibWbui2025
};
