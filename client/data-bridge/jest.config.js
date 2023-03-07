module.exports = {
	moduleFileExtensions: [
		'js',
		'jsx',
		'json',
		'vue',
		'ts',
		'tsx',
	],
	moduleNameMapper: {
		'^@/(.*)$': '<rootDir>/src/$1',
	},
	setupFilesAfterEnv: [ '<rootDir>/tests/config/setup.ts' ],
	snapshotSerializers: [
		'jest-serializer-vue',
	],
	testEnvironment: '<rootDir>/tests/config/JestCustomEnvironment.js',
	testEnvironmentOptions: {
		customExportConditions: [ 'node', 'node-addons' ],
		url: 'https://data-bridge.test/jest',
	},
	testMatch: [
		'**/tests/**/*.spec.(js|jsx|ts|tsx)|**/__tests__/*.(js|jsx|ts|tsx)',
	],
	transform: {
		'^.+\\.vue$': '@vue/vue3-jest',
		'.+\\.(css|styl|less|sass|scss|svg|png|jpg|ttf|woff|woff2)$': 'jest-transform-stub',
		'.+\\.ts$': 'ts-jest', // not sure if needed?
	},
	transformIgnorePatterns: [
		'/node_modules/',
	],
	watchPlugins: [
		'jest-watch-typeahead/filename',
		'jest-watch-typeahead/testname',
	],
	clearMocks: true,
};
