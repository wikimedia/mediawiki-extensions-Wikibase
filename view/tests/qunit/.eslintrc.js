module.exports = {
	extends: [
		'wikimedia/mediawiki/qunit',
		'../../../.eslintrc.js'
	],
	parserOptions: {
		ecmaVersion: 2020
	},
	rules: {
		'no-use-before-define': [
			'error',
			{
				functions: false
			}
		],
		'prefer-arrow-callback': 'off',
		'qunit/resolve-async': 'off',
		'space-unary-ops': 'off',
		'unicorn/prefer-includes': 'off',
		'no-jquery/no-parse-html-literal': 'off'
	}
};
