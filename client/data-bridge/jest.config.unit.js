module.exports = require( './jest.config' );

module.exports.testMatch = [
	'**/tests/unit/**/*.spec.(js|jsx|ts|tsx)|**/__tests__/*.(js|jsx|ts|tsx)',
];

module.exports.collectCoverageFrom = [
	'src/**/*.{ts,vue}',
	'!src/@types/**',
	'!src/datamodel/**',
	'!src/definitions/**',
	'!src/mock-data/**',
	'!src/mock-entry.ts',
];
module.exports.coverageReporters = [ 'lcov', 'text' ];
