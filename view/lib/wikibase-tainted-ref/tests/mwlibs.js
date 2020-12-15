/**
 * In production mode (on-wiki),
 * some of our libraries are loaded as separate ResourceLoader modules,
 * provided by MediaWiki core.
 * This test checks that the versions provided by MediaWiki core
 * are compatible with the requirements in our package.json.
 */
const process = require( 'process' );
const assert = require( 'assert' );
const semver = require( 'semver' );
const requireFromUrl = require( 'require-from-url/sync' );
const packageJson = require( '../package.json' );
const branch = process.env.ZUUL_BRANCH || 'master';

const mwVuex = requireFromUrl(
	`https://raw.githubusercontent.com/wikimedia/mediawiki/${branch}/resources/lib/vuex/vuex.js`,
);
const mwVue = requireFromUrl(
	`https://raw.githubusercontent.com/wikimedia/mediawiki/${branch}/resources/lib/vue/vue.common.prod.js`,
);

assert( semver.satisfies( mwVue.version, packageJson.dependencies.vue ) );
assert( semver.satisfies( mwVuex.version, packageJson.devDependencies.vuex ) );
