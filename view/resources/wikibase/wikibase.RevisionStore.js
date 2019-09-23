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
	 * @constructor
	 * @param {number} baseRev
	 */
	var SELF = function WbRevisionStore( baseRev ) {
		this._revisions = {
			baseRevision: baseRev,
			labelRevision: baseRev,
			descriptionRevision: baseRev,
			aliasesRevision: baseRev,
			sitelinksRevision: {},
			claimRevisions: {}
		};
	};

	/**
	 * @class wikibase.RevisionStore
	 */
	$.extend( SELF.prototype, {

		/**
		 * Returns the original base revision number of the one entity this RevisionStore was
		 * constructed for.
		 *
		 * @return {number}
		 */
		getBaseRevision: function () {
			return this._revisions.baseRevision;
		},

		/**
		 * Returns the currently used revision number for all labels.
		 *
		 * @return {number}
		 */
		getLabelRevision: function () {
			return this._revisions.labelRevision;
		},

		/**
		 * Returns the currently used revision number for all descriptions.
		 *
		 * @return {number}
		 */
		getDescriptionRevision: function () {
			return this._revisions.descriptionRevision;
		},

		/**
		 * Returns the currently used revision number for all aliases.
		 *
		 * @return {number}
		 */
		getAliasesRevision: function () {
			return this._revisions.aliasesRevision;
		},

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
		getSitelinksRevision: function ( siteId ) {
			if ( Object.prototype.hasOwnProperty.call( this._revisions.sitelinksRevision, siteId ) ) {
				return this._revisions.sitelinksRevision[ siteId ];
			}

			return this._revisions.baseRevision;
		},

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
		getClaimRevision: function ( statementGuid ) {
			if ( Object.prototype.hasOwnProperty.call( this._revisions.claimRevisions, statementGuid ) ) {
				return this._revisions.claimRevisions[ statementGuid ];
			}

			return this._revisions.baseRevision;
		},

		/**
		 * Updates the currently used revision number for all labels.
		 *
		 * @param {number} rev
		 */
		setLabelRevision: function ( rev ) {
			this._revisions.labelRevision = rev;
		},

		/**
		 * Updates the currently used revision number for all descriptions.
		 *
		 * @param {number} rev
		 */
		setDescriptionRevision: function ( rev ) {
			this._revisions.descriptionRevision = rev;
		},

		/**
		 * Updates the currently used revision number for all aliases.
		 *
		 * @param {number} rev
		 */
		setAliasesRevision: function ( rev ) {
			this._revisions.aliasesRevision = rev;
		},

		/**
		 * Updates the currently used revision number for a sitelink, per global site identifier.
		 *
		 * @param {number} rev
		 * @param {string} siteId
		 */
		setSitelinksRevision: function ( rev, siteId ) {
			this._revisions.sitelinksRevision[ siteId ] = rev;
		},

		/**
		 * Updates the currently used revision number for a statement, per statement GUID.
		 *
		 * @param {number} rev
		 * @param {string} statementGuid
		 */
		setClaimRevision: function ( rev, statementGuid ) {
			this._revisions.claimRevisions[ statementGuid ] = rev;
		}

	} );

	module.exports = SELF;
}() );
