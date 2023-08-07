/**
 * @typedef {import("../../../lib/wikibase-termbox/dist/wikibase.termbox.init.d.ts")} Termbox
 *
 * @license GPL-2.0-or-later
 */

/**
 * @see {Termbox.TechnicalProblem}
 */
class TechnicalProblem extends Error {
	getContext() {
		return { message: this.message };
	}
}

/**
 * @implements {Termbox.WritingEntityRepository}
 */
class RepoApiWritingEntityRepository {
	/**
	 * @param {RepoApi} repoApi
	 */
	constructor( repoApi ) {
		this._repoApi = repoApi;
	}

	/**
	 * @return {Promise<Object>}
	 */
	saveEntity( entity, baseRevId ) {
		return this._repoApi.editEntity( entity.id, baseRevId, entity )
			.then( ( editEntityResponse ) => {
				this._assertContainsValidEntity( editEntityResponse );

				return {
					entity: editEntityResponse.entity,
					revisionId: editEntityResponse.entity.lastrevid
				};
			} ).catch( ( e ) => {
				throw new TechnicalProblem( e );
			} );
	}

	_assertContainsValidEntity( editEntityResponse ) {
		if (
			!editEntityResponse.entity ||
			!editEntityResponse.entity.id ||
			!editEntityResponse.entity.labels ||
			!editEntityResponse.entity.descriptions ||
			!editEntityResponse.entity.aliases ||
			!editEntityResponse.entity.lastrevid
		) {
			throw new TechnicalProblem( 'invalid entity serialization' );
		}

	}
}

module.exports = RepoApiWritingEntityRepository;
