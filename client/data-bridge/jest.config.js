module.exports = {
	preset: '@vue/cli-plugin-unit-jest/presets/typescript-and-babel',
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
	testMatch: [
		'**/tests/**/*.spec.(js|jsx|ts|tsx)|**/__tests__/*.(js|jsx|ts|tsx)',
	],
	testURL: 'https://data-bridge.test/jest',
	transform: {
		'^.+\\.vue$': 'vue-jest',
		'.+\\.(css|styl|less|sass|scss|svg|png|jpg|ttf|woff|woff2)$': 'jest-transform-stub',
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
