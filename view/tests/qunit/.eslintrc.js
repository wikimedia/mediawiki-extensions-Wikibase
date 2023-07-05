module.exports = {
	extends: [
		'wikimedia/qunit',
		'../../../.eslintrc.js'
	],
	globals: {
		sinon: false
	},
	rules: {
		'no-use-before-define': [
			'error',
			{
				functions: false
			}
		],
		'qunit/resolve-async': 'off',
		'space-unary-ops': 'off',
		'no-jquery/no-parse-html-literal': 'off'
	}
};
