module.exports = {
	extends: [
		'wikimedia',
		'wikimedia/node',
		'wikimedia/language/rules-es2017', // the not-* parts are obsolete after transpiling and polyfills
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
		'@typescript-eslint/member-delimiter-style': 'error',
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

		// replacing from Wikimedia rule set
		'semi': 'off',
		'@typescript-eslint/semi': [ 'error', 'always' ],

		'vue/html-indent': [ 'error', 'tab' ],
		'vue/max-attributes-per-line': [ 'error', {
			singleline: 3,
			multiline: {
				max: 1,
				allowFirstLine: false,
			},
		} ],

		/* copied from eslint-config-wikimedia/client.json;
		 * TODO extend (part of) client.json again
		 * once it doesn’t pull in es5.json
		 */
		'no-alert': 'error',
		'no-console': 'error',
		'no-implied-eval': 'error',
	},
	env: {
		/* TODO also copied from eslint-config-wikimedia/client.json */
		browser: true,
	},
	overrides: {
		files: [ '**/*.ts' ],
		parser: 'vue-eslint-parser',
		rules: {
			'no-undef': 'off',
		},
	},
};
