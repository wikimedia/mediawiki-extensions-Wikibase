/**
 * implements termbox EntityRepository
 *
 * @license GPL-2.0-or-later
 */
module.exports = class EntityLoadedHookEntityRepository {
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
};
