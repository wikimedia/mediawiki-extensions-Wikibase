'use strict';

module.exports = {
	extends: [
		'wikimedia/node',
	],
	env: {
		es6: true,
		node: true,
	},
	rules: {
		'compat/compat': 'off',
		'no-console': 'off',
		'no-process-exit': 'off',
		'mediawiki/valid-package-file-require': 'off',
		'node/no-unsupported-features/node-builtins': 'off',
		'es-x/no-async-functions': 'off',
		'es-x/no-arrow-functions': 'off',
		'es-x/no-block-scoped-variables': 'off',
		'es-x/no-destructuring': 'off',
		'es-x/no-promise': 'off',
		'es-x/no-property-shorthands': 'off',
		'es-x/no-template-literals': 'off',
		'es-x/no-trailing-function-commas': 'off',
	},
};
