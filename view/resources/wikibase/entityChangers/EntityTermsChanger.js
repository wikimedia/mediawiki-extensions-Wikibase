/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var MODULE = wb.entityChangers,
		datamodel = require( 'wikibase.datamodel' );

	const { FingerprintDeserializer, TermMapSerializer } = require( 'wikibase.serialization' );
	const { isCaptchaRequired, extractCaptchaData } = require( 'wikibase.captcha' );

	function chain( tasks ) {
		return tasks.reduce( ( promise, task ) => promise.then( task ), $.Deferred().resolve().promise() );
	}

	MODULE.EntityTermsChanger = class {
		/**
		 * @param {wikibase.api.RepoApi} api
		 * @param {wikibase.RevisionStore} revisionStore
		 * @param {datamodel.Entity} entity
		 */
		constructor( api, revisionStore, entity ) {
			this._api = api;
			this._revisionStore = revisionStore;

			/**
			 * @type {wikibase.entityChangers.AliasesChanger}
			 */
			this._aliasesChanger = new MODULE.AliasesChanger( api, revisionStore, entity );
			/**
			 * @type {wikibase.entityChangers.DescriptionsChanger}
			 */
			this._descriptionsChanger = new MODULE.DescriptionsChanger( api, revisionStore, entity );
			/**
			 * @type {wikibase.entityChangers.LabelsChanger}
			 */
			this._labelsChanger = new MODULE.LabelsChanger( api, revisionStore, entity );
			/**
			 * @type {datamodel.Entity}
			 */
			this._entity = entity;
		}

		/**
		 * @param {datamodel.Fingerprint} newFingerprint
		 * @param {datamodel.Fingerprint} oldFingerprint
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {datamodel.ValueChangeResult} The saved value (a datamodel.Fingerprint)
		 *           and details of any tempUser created
		 *         Rejected parameters:
		 *         - {wikibase.api.RepoApiError}
		 */
		save( newFingerprint, oldFingerprint ) {
			// Bundle only when a captcha is required, since one wbeditentity
			// edit changes the edit summaries and per-field errors; T445056 tracks bundling in all cases.
			if ( isCaptchaRequired() ) {
				return this._saveTogether( newFingerprint, oldFingerprint );
			}
			return this._saveSeparately( newFingerprint, oldFingerprint );
		}

		_saveSeparately( newFingerprint, oldFingerprint ) {
			var labelsChanger = this._labelsChanger,
				descriptionsChanger = this._descriptionsChanger,
				aliasesChanger = this._aliasesChanger,
				changes = [],
				resultFingerprint = newFingerprint,
				tempUserWatcher = new MODULE.TempUserWatcher();

			Array.prototype.push.apply( changes, this._getTermsChanges(
				newFingerprint.getLabels(),
				oldFingerprint.getLabels(),
				( newTerm ) => function () {
					return labelsChanger.setLabel( newTerm, tempUserWatcher ).done( ( savedLabel ) => {
						if ( savedLabel === null ) {
							resultFingerprint.removeLabelFor( newTerm.getLanguageCode() );
						} else {
							resultFingerprint.setLabel( newTerm.getLanguageCode(), savedLabel );
						}
					} ).fail( ( error ) => {
						error.context = { type: 'label', value: newTerm };
					} );
				}
			) );
			Array.prototype.push.apply( changes, this._getTermsChanges(
				newFingerprint.getDescriptions(),
				oldFingerprint.getDescriptions(),
				( newTerm ) => function () {
					return descriptionsChanger.setDescription( newTerm, tempUserWatcher ).done( ( savedDescription ) => {
						if ( savedDescription === null ) {
							resultFingerprint.removeDescriptionFor( newTerm.getLanguageCode() );
						} else {
							resultFingerprint.setDescription( newTerm.getLanguageCode(), savedDescription );
						}
					} ).fail( ( error ) => {
						error.context = { type: 'description', value: newTerm };
					} );
				}
			) );

			this._entity.setFingerprint( oldFingerprint ); // FIXME: For AliasesChanger
			Array.prototype.push.apply( changes, this._getTermsChanges(
				newFingerprint.getAliases(),
				oldFingerprint.getAliases(),
				( newMultiTerm ) => function () {
					return aliasesChanger.setAliases( newMultiTerm, tempUserWatcher ).done( ( savedAliases ) => {
						resultFingerprint.setAliases( newMultiTerm.getLanguageCode(), savedAliases );
					} ).fail( ( error ) => {
						error.context = { type: 'aliases', value: newMultiTerm };
					} );
				}
			) );

			// TODO: These changes should not need to be queued.
			// However, the back-end produces edit conflicts when issuing multiple requests at once.
			// Remove queueing as soon as the back-end is fixed; see bug T74020.
			return chain( changes ).then( () => new MODULE.ValueChangeResult( resultFingerprint, tempUserWatcher ) );
		}

		_saveTogether( newFingerprint, oldFingerprint ) {
			const labels = new datamodel.TermMap();
			const descriptions = new datamodel.TermMap();
			const aliases = {};

			this._getTermsChanges( newFingerprint.getLabels(), oldFingerprint.getLabels() )
				.forEach( ( term ) => labels.setItem( term.getLanguageCode(), term ) );
			this._getTermsChanges( newFingerprint.getDescriptions(), oldFingerprint.getDescriptions() )
				.forEach( ( term ) => descriptions.setItem( term.getLanguageCode(), term ) );
			this._getTermsChanges( newFingerprint.getAliases(), oldFingerprint.getAliases() ).forEach( ( multiTerm ) => {
				const language = multiTerm.getLanguageCode();
				aliases[ language ] = multiTerm.getTexts().map( ( value ) => ( { language, value } ) );
			} );

			const tempUserWatcher = new MODULE.TempUserWatcher();
			if ( labels.isEmpty() && descriptions.isEmpty() && $.isEmptyObject( aliases ) ) {
				return $.Deferred().resolve(
					new MODULE.ValueChangeResult( newFingerprint, tempUserWatcher )
				).promise();
			}

			const termMapSerializer = new TermMapSerializer();
			const data = {
				labels: termMapSerializer.serialize( labels ),
				descriptions: termMapSerializer.serialize( descriptions ),
				aliases
			};

			return this._api.editEntity(
				this._entity.getId(), this._revisionStore.getLabelRevision(), data
			).then( ( result ) => {
				const fingerprint = new FingerprintDeserializer().deserialize( result.entity );
				const revision = result.entity.lastrevid;

				this._revisionStore.setLabelRevision( revision );
				this._revisionStore.setDescriptionRevision( revision );
				this._revisionStore.setAliasesRevision( revision );
				this._entity.setFingerprint( fingerprint );
				tempUserWatcher.processApiResult( result );

				return new MODULE.ValueChangeResult( fingerprint, tempUserWatcher );
			}, ( code, response ) => {
				const error = wb.api.RepoApiError.newFromApiResponse( response, 'save' );
				if ( extractCaptchaData( response ) ) {
					error.code = 'captcha-failed';
				}
				throw error;
			} );
		}

		/**
		 * @param {datamodel.TermMap|datamodel.MultiTermMap} newTerms
		 * @param {datamodel.TermMap|datamodel.MultiTermMap} oldTerms
		 * @param {Function} [getChange]
		 * @return {Array}
		 * @private
		 */
		_getTermsChanges( newTerms, oldTerms, getChange = ( term ) => term ) {
			var changes = [];

			newTerms.each( ( languageCode, newTerm ) => {
				var oldTerm = oldTerms.getItemByKey( languageCode );

				if ( !newTerm.equals( oldTerm ) ) {
					changes.push( getChange( newTerm ) );
				}
			} );

			oldTerms.each( ( languageCode, oldTerm ) => {
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
	};

}( wikibase ) );
