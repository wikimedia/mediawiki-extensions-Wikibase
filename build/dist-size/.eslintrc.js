module.exports = {
	extends: [
		'wikimedia',
		'wikimedia/language/rules-es2017',
	],
	parserOptions: {
		ecmaVersion: 8,
	},
	rules: {
		'comma-dangle': [ 'error', 'always-multiline' ],
		'no-unused-vars': [ 'error', { varsIgnorePattern: '^_' } ],
	},
};
