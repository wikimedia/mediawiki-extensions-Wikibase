'use strict';

/* eslint-disable quotes */
module.exports = {
	extends: [
		"wikimedia/mocha"
	],
	ignorePatterns: [
		".test-user-credentials.json"
	],
	rules: {
		"no-unused-expressions": "off",
		"prefer-arrow-callback": "off",
		"mocha/no-setup-in-describe": 0,
		"mocha/no-skipped-tests": "error"
	}
};
