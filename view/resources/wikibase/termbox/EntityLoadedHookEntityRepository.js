/**
 * @typedef {import("../../../lib/wikibase-termbox/dist/wikibase.termbox.init.d.ts")} Termbox
 *
 * @implements {Termbox.EntityRepository}
 *
 * @license GPL-2.0-or-later
 */
class EntityLoadedHookEntityRepository {
	/**
	 * @param  {mw.hook} hook
	 */
	constructor( hook ) {
		this._entityLoadedHook = hook;
	}

	/**
	 * @return {Promise<Object>}
	 */
	getFingerprintableEntity() {
		return new Promise( ( resolve ) => {
			this._entityLoadedHook.add( function ( entity ) {
				resolve( JSON.parse( JSON.stringify( entity ) ) );
			} );
		} );
	}
}

module.exports = EntityLoadedHookEntityRepository;
