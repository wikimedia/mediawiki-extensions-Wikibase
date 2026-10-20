'use strict';

/**
 * Spec parts that other extensions may require, collected from the `shared`
 * export of each domain spec module. Everything else in those modules is
 * internal and can be moved or reshaped freely.
 */

module.exports = {
	...require( '../../domains/crud/specs/global/request-parts' ).shared,
	...require( '../../domains/crud/specs/global/response-parts' ).shared,
};
