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
	snapshotSerializers: [
		'jest-serializer-vue',
	],
	testEnvironment: 'jsdom',
	testMatch: [
		'**/tests/unit/**/*.spec.(js|jsx|ts|tsx)|**/__tests__/*.(js|jsx|ts|tsx)',
	],
	testURL: 'http://localhost/',
	transform: {
		'^.+\\.vue$': '@vue/vue3-jest',
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
		'!src/mock-entry.ts',
	],
	setupFiles: [
		'./jest.overrides.js',
	],
};
