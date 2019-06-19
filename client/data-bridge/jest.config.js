module.exports = {
	globals: {
		'ts-jest': {
			babelConfig: false
		}
	},
	moduleFileExtensions: [
		'js',
		'jsx',
		'json',
		'vue',
		'ts',
		'tsx'
	],
	moduleNameMapper: {
		'^@/(.*)$': '<rootDir>/src/$1'
	},
	snapshotSerializers: [
		'jest-serializer-vue'
	],
	testMatch: [
		'**/tests/unit/**/*.spec.(js|jsx|ts|tsx)|**/__tests__/*.(js|jsx|ts|tsx)'
	],
	testURL: 'http://localhost/',
	transform: {
		'^.+\\.vue$': 'vue-jest',
		'.+\\.(css|styl|less|sass|scss|svg|png|jpg|ttf|woff|woff2)$': 'jest-transform-stub',
		'^.+\\.tsx?$': 'ts-jest'
	},
	transformIgnorePatterns: [
		'/node_modules/'
	],
	watchPlugins: [
		'jest-watch-typeahead/filename',
		'jest-watch-typeahead/testname'
	]
};
