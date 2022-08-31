'use strict';

const { existsSync } = require( 'fs' );

module.exports = function hasJsonDiffLib() {
	return existsSync( `${__dirname}/../../../../../../../vendor/swaggest/json-diff` );
};
