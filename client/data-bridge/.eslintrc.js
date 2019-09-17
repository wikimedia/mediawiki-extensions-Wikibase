module.exports = {
	extends: [
		'wikimedia',
		'wikimedia/node',
		'wikimedia/language/rules-es2017', // the not-* parts are obsolete after transpiling and polyfills
		'plugin:vue/strongly-recommended',
		'plugin:@typescript-eslint/recommended',
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
	env: {
		/* TODO: taken from eslint-config-wikimedia/client.json */
		browser: true,
	},
	root: true,
	rules: {
		'function-paren-newline': [ 'error', 'consistent' ],
		'filenames/match-exported': 'error',
		'object-shorthand': [ 'error', 'always' ],

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

		/* customize recommended */
		'@typescript-eslint/array-type': [ 'error', 'array' ],
		'@typescript-eslint/ban-types': 'off',
		'@typescript-eslint/camelcase': [ 'error', { 'properties': 'always' } ],
		'@typescript-eslint/explicit-function-return-type': [ 'error', {
			allowExpressions: true,
			allowTypedFunctionExpressions: true,
			allowHigherOrderFunctions: true,
		} ],
		'@typescript-eslint/explicit-member-accessibility': [ 'error', { accessibility: 'explicit' } ],
		'@typescript-eslint/generic-type-naming': [ 'error', '^[A-Z]+$' ],
		// aligned to https://github.com/wikimedia/eslint-config-wikimedia/blob/master/common.json#L21
		'@typescript-eslint/indent': [ 'error', 'tab', { 'SwitchCase': 1 } ],
		'@typescript-eslint/interface-name-prefix': 'off',
		'no-empty-function': 'off',
		'@typescript-eslint/no-empty-function': 'error',
		'@typescript-eslint/no-empty-interface': [ 'error', { allowSingleExtends: true } ],
		'@typescript-eslint/no-extraneous-class': [ 'error', { allowStaticOnly: true } ],
		'@typescript-eslint/no-this-alias': 'error',
		// problematic in TypeScript / ES6
		'@typescript-eslint/no-unused-vars': [ 'error', { argsIgnorePattern: '^_' } ],
		'no-undef': 'error',
		'@typescript-eslint/no-useless-constructor': 'error',
		'@typescript-eslint/prefer-function-type': 'error',
		'@typescript-eslint/type-annotation-spacing': [ 'error', {
			'before': false,
			'after': true,
			overrides: {
				arrow: {
					before: true,
					after: true,
				},
				colon: {
					before: false,
					after: true,
				},
			},
		} ],
		'@typescript-eslint/unified-signatures': 'error',

		// required
		'@typescript-eslint/no-require-imports': 'off',
		'@typescript-eslint/no-var-requires': 'off',

		// we prefer expect().toHaveBeenCalled* over toBeCalled*
		'no-restricted-properties': [
			'error',
			{
				property: 'toBeCalled',
				message: 'Please use toHaveBeenCalled instead.',
			},
			{
				property: 'toBeCalledTimes',
				message: 'Please use toHaveBeenCalledTimes instead.',
			},
			{
				property: 'toBeCalledWith',
				message: 'Please use toHaveBeenCalledWith instead.',
			},
			{
				property: 'lastCalledWith',
				message: 'Please use toHaveBeenLastCalledWith instead.',
			},
			{
				property: 'nthCalledWith',
				message: 'Please use toHaveBeenNthCalledWith instead.',
			},
			{
				property: 'toReturn',
				message: 'Please use tohaveReturned instead.',
			},
			{
				property: 'toReturnTimes',
				message: 'Please use toHaveReturnedTimes instead.',
			},
			{
				property: 'toReturnWith',
				message: 'Please use toHaveReturnedTimes instead.',
			},
			{
				property: 'lastReturnedWith',
				message: 'Please use toHaveLastReturnedWith instead.',
			},
			{
				property: 'nthReturnedWith',
				message: 'Please use toHaveNthReturnedWith instead.',
			},
		],
	},
	overrides: {
		files: [ '**/*.ts' ],
		parser: 'vue-eslint-parser',
		rules: {
			'no-undef': 'off',
		},
	},
};
