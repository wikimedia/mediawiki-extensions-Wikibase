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
	},
};
