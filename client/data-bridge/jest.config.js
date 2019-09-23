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
	setupTestFrameworkScriptFile: '<rootDir>/tests/config/setup.ts',
	snapshotSerializers: [
		'jest-serializer-vue',
	],
	testEnvironment: '<rootDir>/tests/config/JestCustomEnvironment.js',
	testMatch: [
		'**/tests/**/*.spec.(js|jsx|ts|tsx)|**/__tests__/*.(js|jsx|ts|tsx)',
	],
	testURL: 'http://localhost/',
	transform: {
		'^.+\\.vue$': 'vue-jest',
		'.+\\.(css|styl|less|sass|scss|svg|png|jpg|ttf|woff|woff2)$': 'jest-transform-stub',
		'^.+\\.tsx?$': 'ts-jest',
	},
	transformIgnorePatterns: [
		'/node_modules/',
	],
	watchPlugins: [
		'jest-watch-typeahead/filename',
		'jest-watch-typeahead/testname',
	],
	collectCoverageFrom: [
		'src/**/*.{ts,vue}',
		'!src/@types/**',
		'!src/datamodel/**',
		'!src/definitions/**',
		'!src/mock-data/**',
		'!src/mock-entry.ts',
	],
	clearMocks: true,
};
