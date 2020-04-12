module.exports = {
	env: {
		mocha: true,
	},
	extends: [
		'plugin:wdio/recommended',
	],
	plugins: [
		'wdio',
	],
	rules: {
		'comma-dangle': [
			'error', {
				'arrays': 'always-multiline',
				'objects': 'always-multiline',
				'imports': 'always-multiline',
				'exports': 'always-multiline',
				'functions': 'never',
			},
		],
		'max-len': [
			'error', {
				code: 120,
				ignoreTemplateLiterals: true,
			},
		],

		'jest-formatting/padding-around-describe-blocks': 'off',
		'jest-formatting/padding-around-test-blocks': 'off',
	},
};
