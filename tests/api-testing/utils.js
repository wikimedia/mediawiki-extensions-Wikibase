'use strict';

const { action } = require( 'api-testing' );

/**
 * Return a function which skips the current test suite
 * if any of the required extensions isn’t installed in the target wiki.
 *
 * Usage:
 *
 *     before( 'require extensions', requireExtensions( [ ... ] ) );
 *
 * @param {string[]} requiredExtensions
 * @return {Function}
 */
function requireExtensions( requiredExtensions ) {
	return async function () {
		const installedExtensions = ( await action.getAnon().meta(
			'siteinfo',
			{ siprop: 'extensions' },
			'extensions',
		) ).map( ( extension ) => extension.name );
		const missingExtensions = requiredExtensions.filter(
			( requiredExtension ) => !installedExtensions.includes( requiredExtension ),
		);
		if ( missingExtensions.length ) {
			this.skip();
		}
	};
}

module.exports = {
	requireExtensions,
};
