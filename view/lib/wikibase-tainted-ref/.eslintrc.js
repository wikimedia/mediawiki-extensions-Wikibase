module.exports = {
	extends: [
		'wikimedia',
		'wikimedia/node',
		'wikimedia/language/rules-es2017', // the not-* parts are obsolete after transpiling and polyfills
		'plugin:vue/strongly-recommended',
		'@wmde/wikimedia-typescript',
	],

	plugins: [
		'filenames',
	],

	parser: 'vue-eslint-parser',

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

		// for ResourceLoader `require`
		'@typescript-eslint/no-require-imports': 'off',
		'@typescript-eslint/no-var-requires': 'off',

		'@typescript-eslint/ban-types': 'off',
	},

	overrides: [
		{
			files: [ '**/*.ts' ],
			parser: 'vue-eslint-parser',
			rules: {
				'no-undef': 'off',
				'no-use-before-define': 'off',
				'@typescript-eslint/ban-types': 'off',
			},
		},
		{
			files: [ '**/*.js' ],
			rules: {
				'@typescript-eslint/explicit-function-return-type': 'off',
				'@typescript-eslint/explicit-member-accessibility': 'off',
			},
		},
	],

	parserOptions: {
		parser: '@typescript-eslint/parser',
	},
};
