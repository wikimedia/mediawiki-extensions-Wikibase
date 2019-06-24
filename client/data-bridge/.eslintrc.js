module.exports = {
	extends: [
		'wikimedia',
		'wikimedia/node',
		'wikimedia/client',
		'wikimedia/language/es6',
		'wikimedia/language/es2017',
		'plugin:vue/strongly-recommended',
	],
	plugins: [
		'@typescript-eslint',
		'filenames',
	],
	parser: 'vue-eslint-parser',
	parserOptions: {
		parser: '@typescript-eslint/parser',
		sourceType: 'module',
		ecmaFeatures: {
			impliedStrict: true,
		},
	},
	root: true,
	rules: {
		'function-paren-newline': [ 'error', 'consistent' ],
		'@typescript-eslint/type-annotation-spacing': [ 'error' ],
		'@typescript-eslint/explicit-function-return-type': [ 'error', {
			allowExpressions: true,
			allowTypedFunctionExpressions: true,
			allowHigherOrderFunctions: true,
		} ],
		'generic-type-naming': '^[A-Z]+$',
		'@typescript-eslint/no-empty-interface': [ 'error', { 'allowSingleExtends': true } ],
		'@typescript-eslint/no-misused-new': 'error',
		'@typescript-eslint/no-this-alias': 'error',
		'no-useless-constructor': 'error',
		'filenames/match-exported': 'error',
		'object-shorthand': [ 'error', 'always' ],
		'@typescript-eslint/explicit-member-accessibility': [ 'error', { accessibility: 'explicit' } ],

		// problematic in TypeScript / ES6
		'no-unused-vars': 'off',
		'@typescript-eslint/no-unused-vars': [ 'error', { argsIgnorePattern: '^_' } ],
		'no-undef': 'error',

		// diverging from Wikimedia rule set
		'max-len': [ 'error', 120 ],
		'comma-dangle': [ 'error', {
			arrays: 'always-multiline',
			objects: 'always-multiline',
			imports: 'always-multiline',
			exports: 'always-multiline',
			functions: 'always-multiline',
		} ],
		'operator-linebreak': 'off',
		'quote-props': 'off',
		'valid-jsdoc': 'off',

		'vue/html-indent': [ 'error', 'tab' ],
		'vue/max-attributes-per-line': [ 'error', {
			singleline: 3,
			multiline: {
				max: 1,
				allowFirstLine: false,
			},
		} ],

		'no-restricted-properties': 'off',

		/* remove the following if
		 * https://github.com/wikimedia/eslint-config-wikimedia/pull/171
		 * is active in eslint-config-wikimedia
		 */
		'prefer-const': 'error',
	},
	overrides: {
		files: [ '**/*.ts' ],
		parser: 'vue-eslint-parser',
		rules: {
			'no-undef': 'off',
		},
	},
};
