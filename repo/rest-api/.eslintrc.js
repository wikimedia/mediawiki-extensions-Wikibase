'use strict';

/* eslint-disable quotes */
module.exports = {
	root: true,
	extends: [ "../../reuse-team-shared.eslintrc.js" ],
	ignorePatterns: [
		"node_modules/",
		"vendor",
		"dist/",
		"docs/",
		".redocly.lint-ignore.yaml",

		// auto-generated openapi files
		"specs/openapi-joined.json",
		"src/openapi.json"
	],
	rules: {
		"no-unused-expressions": "off",
		"prefer-arrow-callback": "off",
	}
};
