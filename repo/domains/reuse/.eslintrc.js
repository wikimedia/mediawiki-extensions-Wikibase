'use strict';

module.exports = {
	root: true,
	extends: [ '../../../reuse-team-shared.eslintrc.js' ],
	ignorePatterns: [
		'**/eslint.config.js',
		'graphiql-explorer/'
	],
	rules: {
		'no-console': 'error',
	}
};
