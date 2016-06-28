/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.entityChangers;

	function chain( tasks ) {
		return tasks.reduce( function( promise, task ) {
			return promise.then( task );
		}, $.Deferred().resolve().promise() );
	}

	/**
	 * @param {wikibase.api.RepoApi} api
	 * @param {wikibase.RevisionStore} revisionStore
	 * @param {wikibase.datamodel.Entity} entity
	 */
	var SELF = MODULE.EntityTermsChanger = function WbEntityChangersEntityTermsChanger( api, revisionStore, entity ) {
		this._aliasesChanger = new MODULE.AliasesChanger( api, revisionStore, entity );
		this._descriptionsChanger = new MODULE.DescriptionsChanger( api, revisionStore, entity );
		this._labelsChanger = new MODULE.LabelsChanger( api, revisionStore, entity );
		this._entity = entity;
	};

	$.extend( SELF.prototype, {
		/**
		 * @type {wikibase.datamodel.Entity}
		 */
		_entity: null,

		/**
		 * @type {wikibase.entityChangers.AliasesChanger}
		 */
		_aliasesChanger: null,

		/**
		 * @type {wikibase.entityChangers.DescriptionsChanger}
		 */
		_descriptionsChanger: null,

		/**
		 * @type {wikibase.entityChangers.LabelsChanger}
		 */
		_labelsChanger: null,

		/**
		 * @param {wikibase.datamodel.Fingerprint} newFingerprint
		 * @param {wikibase.datamodel.Fingerprint} oldFingerprint
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {wikibase.datamodel.Fingerprint} The saved fingerprint
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		save: function( newFingerprint, oldFingerprint ) {
			var changes = [];
			var resultFingerprint = newFingerprint;

			var aliasesChanger = this._aliasesChanger;
			var descriptionsChanger = this._descriptionsChanger;
			var labelsChanger = this._labelsChanger;

			changes = changes.concat( this._getTermsChanges(
				newFingerprint.getLabels(),
				oldFingerprint.getLabels(),
				function( newTerm ) {
					return function() {
						return labelsChanger.setLabel( newTerm ).done( function( savedLabel ) {
							resultFingerprint.setLabel( newTerm.getLanguageCode(), savedLabel );
						} ).fail( function( error ) {
							error.context = { type: 'label', value: newTerm };
						} );
					};
				}
			) );
			changes = changes.concat( this._getTermsChanges(
				newFingerprint.getDescriptions(),
				oldFingerprint.getDescriptions(),
				function( newTerm ) {
					return function() {
						return descriptionsChanger.setDescription( newTerm ).done( function( savedDescription ) {
							resultFingerprint.setDescription( newTerm.getLanguageCode(), savedDescription );
						} ).fail( function( error ) {
							error.context = { type: 'description', value: newTerm };
						} );
					};
				}
			) );

			this._entity.setFingerprint( oldFingerprint ); // FIXME: For AliasesChanger
			changes = changes.concat( this._getTermsChanges(
				newFingerprint.getAliases(),
				oldFingerprint.getAliases(),
				function( newMultiTerm ) {
					return function() {
						return aliasesChanger.setAliases( newMultiTerm ).done( function( savedAliases ) {
							resultFingerprint.setAliases( newMultiTerm.getLanguageCode(), savedAliases );
						} ).fail( function( error ) {
							error.context = { type: 'aliases', value: newMultiTerm };
						} );
					};
				}
			) );

			// TODO: These changes should not need to be queued.
			// However, the back-end produces edit conflicts when issuing multiple requests at once.
			// Remove queueing as soon as the back-end is fixed; see bug T74020.
			return chain( changes ).then( function() {
				return resultFingerprint;
			} );
		},

		/**
		 * @param {wikibase.datamodel.TermMap|wikibase.datamodel.MultiTermMap} newTerms
		 * @param {wikibase.datamodel.TermMap|wikibase.datamodel.MultiTermMap} oldTerms
		 * @param {Function} getChange
		 * @return {Function[]}
		 * @private
		 */
		_getTermsChanges: function( newTerms, oldTerms, getChange ) {
			var changes = [];

			newTerms.each( function( languageCode, newTerm ) {
				var oldTerm = oldTerms.getItemByKey( languageCode );
				if ( !newTerm.equals( oldTerm ) ) {
					changes.push( getChange( newTerm ) );
				}
			} );

			oldTerms.each( function( languageCode, oldTerm ) {
				if (
					!newTerms.hasItemForKey( languageCode ) ||
					// There are also MultiTerms where this does not apply
					( oldTerm instanceof wb.datamodel.Term && newTerms.getItemByKey( languageCode ).getText() === '' )
				) {
					changes.push( getChange(
						new oldTerm.constructor( languageCode, oldTerm instanceof wb.datamodel.Term ? '' : [] )
					) );
				}
			} );

			return changes;
		}
	} );

}( wikibase, jQuery ) );
