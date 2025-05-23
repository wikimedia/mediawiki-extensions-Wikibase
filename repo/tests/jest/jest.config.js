module.exports = {
	clearMocks: true,
	moduleFileExtensions: [
		'js',
		'vue'
	],
	setupFiles: [
		'./jest.setup.js'
	],
	testEnvironment: 'jsdom',
	testEnvironmentOptions: {
		customExportConditions: [ 'node', 'node-addons' ]
	},
	transform: {
		'.*\\.(vue)$': '<rootDir>/../../../node_modules/@vue/vue3-jest'
	}
};
