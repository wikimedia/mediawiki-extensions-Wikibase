module.exports = {
	clearMocks: true,
	moduleFileExtensions: [
		'js',
		'vue'
	],
	moduleNameMapper: {
		// Resolve the wikibase.captcha ResourceLoader alias to its source file.
		'^wikibase\\.captcha$': '<rootDir>/../../../view/resources/wikibase/captcha/resolveCaptcha.js'
	},
	setupFiles: [
		'./jest.setup.js'
	],
	setupFilesAfterEnv: [
		'./jest.setupAfterEnv.js'
	],
	testEnvironment: 'jsdom',
	testEnvironmentOptions: {
		customExportConditions: [ 'node', 'node-addons' ]
	},
	transform: {
		'.*\\.(vue)$': '<rootDir>/../../../node_modules/@vue/vue3-jest'
	}
};
