'use strict';

/* eslint-disable quote-props, quotes */
module.exports = {
	root: true,
	extends: [
		"wikimedia",
		"wikimedia/node",
		"wikimedia/language/es2022"
	],
	env: {
		node: true,
	},
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
		"max-len": [ "warn", { "code": 130 } ],
		"no-unused-expressions": "off",
		"prefer-arrow-callback": "off",
		"template-curly-spacing": "off",
		"camelcase": "off",
		"comma-dangle": "off",
		"no-implicit-coercion": [ "error", { "disallowTemplateShorthand": true } ]
	},
	overrides: [
		{
			files: [ "*.json" ],
			rules: {
				"max-len": "off",
			}
		}
	]
};
