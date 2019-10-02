/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var MODULE = wb.entityChangers,
		datamodel = require( 'wikibase.datamodel' );

	function chain( tasks ) {
		return tasks.reduce( function ( promise, task ) {
			return promise.then( task );
		}, $.Deferred().resolve().promise() );
	}

	/**
	 * @param {wikibase.api.RepoApi} api
	 * @param {wikibase.RevisionStore} revisionStore
	 * @param {datamodel.Entity} entity
	 */
	var SELF = MODULE.EntityTermsChanger = function WbEntityChangersEntityTermsChanger( api, revisionStore, entity ) {
		this._aliasesChanger = new MODULE.AliasesChanger( api, revisionStore, entity );
		this._descriptionsChanger = new MODULE.DescriptionsChanger( api, revisionStore, entity );
		this._labelsChanger = new MODULE.LabelsChanger( api, revisionStore, entity );
		this._entity = entity;
	};

	$.extend( SELF.prototype, {
		/**
		 * @type {datamodel.Entity}
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
		 * @param {datamodel.Fingerprint} newFingerprint
		 * @param {datamodel.Fingerprint} oldFingerprint
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {datamodel.Fingerprint} The saved fingerprint
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		save: function ( newFingerprint, oldFingerprint ) {
			var labelsChanger = this._labelsChanger,
				descriptionsChanger = this._descriptionsChanger,
				aliasesChanger = this._aliasesChanger,
				changes = [],
				resultFingerprint = newFingerprint;

			Array.prototype.push.apply( changes, this._getTermsChanges(
				newFingerprint.getLabels(),
				oldFingerprint.getLabels(),
				function ( newTerm ) {
					return function () {
						return labelsChanger.setLabel( newTerm ).done( function ( savedLabel ) {
							if ( savedLabel === null ) {
								resultFingerprint.removeLabelFor( newTerm.getLanguageCode() );
							} else {
								resultFingerprint.setLabel( newTerm.getLanguageCode(), savedLabel );
							}
						} ).fail( function ( error ) {
							error.context = { type: 'label', value: newTerm };
						} );
					};
				}
			) );
			Array.prototype.push.apply( changes, this._getTermsChanges(
				newFingerprint.getDescriptions(),
				oldFingerprint.getDescriptions(),
				function ( newTerm ) {
					return function () {
						return descriptionsChanger.setDescription( newTerm ).done( function ( savedDescription ) {
							if ( savedDescription === null ) {
								resultFingerprint.removeDescriptionFor( newTerm.getLanguageCode() );
							} else {
								resultFingerprint.setDescription( newTerm.getLanguageCode(), savedDescription );
							}
						} ).fail( function ( error ) {
							error.context = { type: 'description', value: newTerm };
						} );
					};
				}
			) );

			this._entity.setFingerprint( oldFingerprint ); // FIXME: For AliasesChanger
			Array.prototype.push.apply( changes, this._getTermsChanges(
				newFingerprint.getAliases(),
				oldFingerprint.getAliases(),
				function ( newMultiTerm ) {
					return function () {
						return aliasesChanger.setAliases( newMultiTerm ).done( function ( savedAliases ) {
							resultFingerprint.setAliases( newMultiTerm.getLanguageCode(), savedAliases );
						} ).fail( function ( error ) {
							error.context = { type: 'aliases', value: newMultiTerm };
						} );
					};
				}
			) );

			// TODO: These changes should not need to be queued.
			// However, the back-end produces edit conflicts when issuing multiple requests at once.
			// Remove queueing as soon as the back-end is fixed; see bug T74020.
			return chain( changes ).then( function () {
				return resultFingerprint;
			} );
		},

		/**
		 * @param {datamodel.TermMap|datamodel.MultiTermMap} newTerms
		 * @param {datamodel.TermMap|datamodel.MultiTermMap} oldTerms
		 * @param {Function} getChange
		 * @return {Function[]}
		 * @private
		 */
		_getTermsChanges: function ( newTerms, oldTerms, getChange ) {
			var changes = [];

			newTerms.each( function ( languageCode, newTerm ) {
				var oldTerm = oldTerms.getItemByKey( languageCode );

				if ( !newTerm.equals( oldTerm ) ) {
					changes.push( getChange( newTerm ) );
				}
			} );

			oldTerms.each( function ( languageCode, oldTerm ) {
				var isTerm = oldTerm instanceof datamodel.Term;

				if ( !newTerms.hasItemForKey( languageCode )
					// There are also MultiTerms where this does not apply
					|| ( isTerm && newTerms.getItemByKey( languageCode ).getText() === '' )
				) {
					changes.push( getChange(
						new oldTerm.constructor( languageCode, isTerm ? '' : [] )
					) );
				}
			} );

			return changes;
		}
	} );

}( wikibase ) );
