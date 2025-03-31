/**
 * JavaScript storing revision ids about different sections.
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher
 */
( function () {
	'use strict';

	/**
	 * Offers access to stored revision ids
	 *
	 * @class wikibase.RevisionStore
	 */
	module.exports = class {
		/**
		 * @param {number} baseRev
		 */
		constructor( baseRev ) {
			this._revisions = {
				baseRevision: baseRev,
				labelRevision: baseRev,
				descriptionRevision: baseRev,
				aliasesRevision: baseRev,
				sitelinksRevision: {},
				claimRevisions: {}
			};
		}

		/**
		 * Returns the original base revision number of the one entity this RevisionStore was
		 * constructed for.
		 *
		 * @return {number}
		 */
		getBaseRevision() {
			return this._revisions.baseRevision;
		}

		/**
		 * Returns the currently used revision number for all labels.
		 *
		 * @return {number}
		 */
		getLabelRevision() {
			return this._revisions.labelRevision;
		}

		/**
		 * Returns the currently used revision number for all descriptions.
		 *
		 * @return {number}
		 */
		getDescriptionRevision() {
			return this._revisions.descriptionRevision;
		}

		/**
		 * Returns the currently used revision number for all aliases.
		 *
		 * @return {number}
		 */
		getAliasesRevision() {
			return this._revisions.aliasesRevision;
		}

		/**
		 * Returns the currently used revision number for a sitelink, per global site identifier.
		 * Falls back to the base revision this RevisionStore was constructed with.
		 *
		 * Note this is not globally unique! Different entities with sitelinks can not share the
		 * same RevisionStore!
		 *
		 * @param {string} siteId
		 * @return {number}
		 */
		getSitelinksRevision( siteId ) {
			if ( Object.prototype.hasOwnProperty.call( this._revisions.sitelinksRevision, siteId ) ) {
				return this._revisions.sitelinksRevision[ siteId ];
			}

			return this._revisions.baseRevision;
		}

		/**
		 * Returns the currently used revision number for a statement, per statement GUID.
		 *
		 * Since statement GUIDs are globally unique, different entities with statements can
		 * theoretically share the same RevisionStore. However, this is not how RevisionStores are
		 * meant to be used, and highly discouraged.
		 *
		 * @param {string} statementGuid
		 * @return {number}
		 */
		getClaimRevision( statementGuid ) {
			if ( Object.prototype.hasOwnProperty.call( this._revisions.claimRevisions, statementGuid ) ) {
				return this._revisions.claimRevisions[ statementGuid ];
			}

			return this._revisions.baseRevision;
		}

		/**
		 * Updates the currently used revision number for all labels.
		 *
		 * @param {number} rev
		 */
		setLabelRevision( rev ) {
			this._revisions.labelRevision = rev;
		}

		/**
		 * Updates the currently used revision number for all descriptions.
		 *
		 * @param {number} rev
		 */
		setDescriptionRevision( rev ) {
			this._revisions.descriptionRevision = rev;
		}

		/**
		 * Updates the currently used revision number for all aliases.
		 *
		 * @param {number} rev
		 */
		setAliasesRevision( rev ) {
			this._revisions.aliasesRevision = rev;
		}

		/**
		 * Updates the currently used revision number for a sitelink, per global site identifier.
		 *
		 * @param {number} rev
		 * @param {string} siteId
		 */
		setSitelinksRevision( rev, siteId ) {
			this._revisions.sitelinksRevision[ siteId ] = rev;
		}

		/**
		 * Updates the currently used revision number for a statement, per statement GUID.
		 *
		 * @param {number} rev
		 * @param {string} statementGuid
		 */
		setClaimRevision( rev, statementGuid ) {
			this._revisions.claimRevisions[ statementGuid ] = rev;
		}

	};

}() );
