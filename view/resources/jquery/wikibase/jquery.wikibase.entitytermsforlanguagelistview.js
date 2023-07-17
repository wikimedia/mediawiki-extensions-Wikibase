/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
( function () {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget,
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * Displays multiple fingerprints (see jQuery.wikibase.entitytermsforlanguageview).
	 *
	 * @extends jQuery.ui.EditableTemplatedWidget
	 *
	 * @option {datamodel.Fingerprint} value
	 *
	 * @option {string[]} userLanguages
	 *         A list of languages for which terms should be displayed initially.
	 *
	 * @event change
	 *        - {jQuery.Event}
	 *        - {string} Language code the change was made in.
	 *
	 * @event afterstartediting
	 *       - {jQuery.Event}
	 *
	 * @event afterstopediting
	 *        - {jQuery.Event}
	 *        - {boolean} Whether to drop the value.
	 *
	 * @event toggleerror
	 *        - {jQuery.Event}
	 *        - {Error|null}
	 */
	$.widget( 'wikibase.entitytermsforlanguagelistview', PARENT, {
		options: {
			template: 'wikibase-entitytermsforlanguagelistview',
			templateParams: [
				mw.msg( 'wikibase-entitytermsforlanguagelistview-language' ),
				mw.msg( 'wikibase-entitytermsforlanguagelistview-label' ),
				mw.msg( 'wikibase-entitytermsforlanguagelistview-description' ),
				mw.msg( 'wikibase-entitytermsforlanguagelistview-aliases' ),
				'' // entitytermsforlanguageview
			],
			templateShortCuts: {
				$header: '.wikibase-entitytermsforlanguagelistview-header',
				$listview: '.wikibase-entitytermsforlanguagelistview-listview'
			},
			value: null,
			userLanguages: []
		},

		/**
		 * @type {jQuery}
		 */
		$listview: null,

		/**
		 * @type {jQuery}
		 */
		$entitytermsforlanguagelistviewMore: null,

		/**
		 * @type {boolean} Has the "show all languages" button been clicked and this click been tracked?
		 */
		_showAllLanguagesTracked: false,

		/**
		 * @type {Object} Map of language codes pointing to list items (in the form of jQuery nodes).
		 */
		_moreLanguagesItems: {},

		/**
		 * @type {string[]} List of languages shown per default.
		 */
		_defaultLanguages: [],

		/**
		 * @type {OO.ui.PopupWidget|null}
		 */
		_popup: null,

		/**
		 * @see jQuery.ui.TemplatedWidget._create
		 */
		_create: function () {
			if ( !( this.options.value instanceof datamodel.Fingerprint )
				|| !Array.isArray( this.options.userLanguages )
			) {
				throw new Error( 'Required option(s) missing' );
			}

			PARENT.prototype._create.call( this );
			this._defaultLanguages = this.options.userLanguages;

			this._amendDefaultLanguages();

			this._verifyExistingDom();
			this._createListView();

			this.element.addClass( 'wikibase-entitytermsforlanguagelistview' );
		},

		_amendDefaultLanguages: function () {
			if ( !mw.config.get( 'wbEnableMulLanguageCode' ) ) {
				return;
			}
			// eslint-disable-next-line es-x/no-array-prototype-includes
			if ( this._defaultLanguages.includes( 'mul' ) ) {
				return;
			}

			if ( mw.config.get( 'wbTmpAlwaysShowMulLanguageCode' ) === false ) {
				// temporarily show "mul" only if it has a label or alias, see T330217 for removing this block
				if ( !( 'mul' in this._getMoreLanguages() ) ) {
					return;
				}
			}

			this._defaultLanguages.push( 'mul' );
		},

		/**
		 * @see jQuery.ui.TemplatedWidget.destroy
		 */
		destroy: function () {
			// When destroying a widget not initialized properly, shortcuts will not have been created.
			if ( this.$listview ) {
				// When destroying a widget not initialized properly, listview will not have been created.
				var listview = this.$listview.data( 'listview' );

				if ( listview ) {
					listview.destroy();
				}
			}

			if ( this.$entitytermsforlanguagelistviewMore ) {
				this.$entitytermsforlanguagelistviewMore.remove();
			}

			this.element.removeClass( 'wikibase-entitytermsforlanguagelistview' );
			PARENT.prototype.destroy.call( this );
		},

		_verifyExistingDom: function () {
			var $entitytermsforlanguageview = this.element
				.find( '.wikibase-entitytermsforlanguageview' );

			if ( $entitytermsforlanguageview.length === 0 ) {
				// No need to verify an empty DOM
				return;
			}

			// Scrape languages from static HTML:
			var mismatchAt = null,
				languages = this._defaultLanguages;
			$entitytermsforlanguageview.each( function ( i ) {
				var match = $( this )
					.attr( 'class' )
					.match( /(?:^|\s)wikibase-entitytermsforlanguageview-(\S+)/ );
				if ( match && match[ 1 ] !== languages[ i ] ) {
					if ( match[ 1 ] !== 'mul' ) {
						// "mul" might be included in the existing term box, but we want it to be after
						// everything else, thus discarding it is expected.
						mw.log.warn( 'Existing entitytermsforlanguagelistview DOM does not match configured languages' );
					}
					mismatchAt = i;
					return false;
				}
			} );

			if ( mismatchAt !== null ) {
				$entitytermsforlanguageview.slice( mismatchAt ).remove();
			}
		},

		/**
		 * Creates the listview widget managing the entitytermsforlanguageview widgets
		 *
		 * @private
		 */
		_createListView: function () {
			var self = this,
				listItemWidget = $.wikibase.entitytermsforlanguageview,
				prefix = listItemWidget.prototype.widgetEventPrefix;

			// Fully encapsulate child widgets by suppressing their events:
			this.element
			.on( prefix + 'change.' + this.widgetName, function ( event, lang ) {
				event.stopPropagation();
				// The only event handler for this is in entitytermsview.
				self._trigger( 'change', null, [ lang ] );
			} )
			.on( prefix + 'toggleerror.' + this.widgetName, function ( event, error ) {
				event.stopPropagation();
				self.setError( error );
			} )
			.on(
				[
					prefix + 'create.' + this.widgetName,
					prefix + 'afterstartediting.' + this.widgetName,
					prefix + 'afterstopediting.' + this.widgetName,
					prefix + 'disable.' + this.widgetName
				].join( ' ' ),
				function ( event ) {
					event.stopPropagation();
				}
			);

			this.$listview
			.listview( {
				listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
					listItemWidget: listItemWidget,
					newItemOptionsFn: function ( value ) {
						return {
							allLanguageLabels: self.options.value.getLabels(),
							value: value
						};
					}
				} ),
				value: this._defaultLanguages.map( function ( lang ) {
					return self._getValueForLanguage( lang );
				} ),
				listItemNodeName: 'TR'
			} );

			if ( !this.element.find( '.wikibase-entitytermsforlanguagelistview-more' ).length ) {
				this._createEntitytermsforlanguagelistviewMore();
			}

			if (
				mw.user.isNamed() &&
				!mw.user.options.get( 'wb-dont-show-again-mul-popup' )
			) {
				this._addPulsatingDotToMul();
			}
		},

		/**
		 * Creates a button which allows the user to show terms in all languages available.
		 *
		 * @private
		 */
		_createEntitytermsforlanguagelistviewMore: function () {
			if ( !this._hasMoreLanguages() ) {
				return;
			}

			var $moreLanguagesButton = $( '<a>' )
				.attr( 'href', '#' )
				.on( 'click', this._onMoreLanguagesButtonClicked.bind( this ) );
			this._toggleMoreLanguagesButton( $moreLanguagesButton );

			this.$entitytermsforlanguagelistviewMore = $( '<div>' )
				.addClass( 'wikibase-entitytermsforlanguagelistview-more' )
				.append( $moreLanguagesButton );

			this.element.after( this.$entitytermsforlanguagelistviewMore );
		},

		/**
		 * @return {boolean} If there are more languages to display.
		 * @private
		 */
		_hasMoreLanguages: function () {
			var fingerprint = this.options.value,
				minLength = this._defaultLanguages.length;

			if ( fingerprint.getLabels().length > minLength
				|| fingerprint.getDescriptions().length > minLength
				|| fingerprint.getAliases().length > minLength
			) {
				return true;
			}

			return !$.isEmptyObject( this._getMoreLanguages() );
		},

		/**
		 * Click handler for more languages button.
		 *
		 * @private
		 */
		_onMoreLanguagesButtonClicked: function ( event ) {
			var $button = $( event.target );

			if ( !this._isMoreLanguagesExpanded() ) {
				this._addMoreLanguages();
				this._trackAllLanguagesShown();
			} else {
				var previousTop = $button.offset().top;
				this._removeMoreLanguages();
				this._scrollUp( $button, previousTop );
			}

			this._toggleMoreLanguagesButton( $button );
			return false;
		},

		/**
		 * Toggle more language button text between the "wikibase-entitytermsforlanguagelistview-less"
		 * and "wikibase-entitytermsforlanguagelistview-more" messages.
		 *
		 * @param {jQuery} $button
		 * @private
		 */
		_toggleMoreLanguagesButton: function ( $button ) {
			$button.text( mw.msg(
				this._isMoreLanguagesExpanded() ?
					'wikibase-entitytermsforlanguagelistview-less' :
					'wikibase-entitytermsforlanguagelistview-more'
			) );
		},

		_trackAllLanguagesShown: function () {
			if ( this._showAllLanguagesTracked ) {
				return;
			}
			mw.track( 'event.WikibaseTermboxInteraction', {
				actionType: 'all'
			} );
			this._showAllLanguagesTracked = true;
		},

		/**
		 * @return {boolean}
		 * @private
		 */
		_isMoreLanguagesExpanded: function () {
			return !$.isEmptyObject( this._moreLanguagesItems );
		},

		/**
		 * Add terms in "more" languages to the list view, ordered by language code.
		 *
		 * @private
		 */
		_addMoreLanguages: function () {
			var listview = this.$listview.data( 'listview' ),
				lia = listview.listItemAdapter(),
				self = this;

			Object.keys( this._getMoreLanguages() ).sort().forEach( function ( languageCode ) {
				var $item = listview.addItem( self._getValueForLanguage( languageCode ) );
				if ( self.isInEditMode() ) {
					lia.liInstance( $item ).startEditing();
				}
				self._moreLanguagesItems[ languageCode ] = $item;
			} );
		},

		/**
		 * Remove terms in "more" languages from the list view.
		 *
		 * @private
		 */
		_removeMoreLanguages: function () {
			var listview = this.$listview.data( 'listview' );

			for ( var languageCode in this._moreLanguagesItems ) {
				listview.removeItem( this._moreLanguagesItems[ languageCode ] );
			}

			this._moreLanguagesItems = {};
		},

		/**
		 * @return {Object} Unsorted map of "more" language codes in this fingerprint.
		 * @private
		 */
		_getMoreLanguages: function () {
			var fingerprint = this.options.value,
				languages = {};

			fingerprint.getLabels().each( function ( lang ) {
				languages[ lang ] = lang;
			} );
			fingerprint.getDescriptions().each( function ( lang ) {
				languages[ lang ] = lang;
			} );
			fingerprint.getAliases().each( function ( lang ) {
				languages[ lang ] = lang;
			} );

			this._defaultLanguages.forEach( function ( lang ) {
				delete languages[ lang ];
			} );

			return languages;
		},

		/**
		 * @param {jQuery} $this
		 * @param {number} previousTop
		 * @private
		 */
		_scrollUp: function ( $this, previousTop ) {
			var top = $this.offset().top;

			if ( top < $( window ).scrollTop() ) {
				// This does not only keep the toggler visible, it also updates all stick(y)nodes.
				window.scrollBy( 0, top - previousTop );
			}
		},

		/**
		 * Click handler to open Popup when clicking pulsating dot for mul language.
		 *
		 * @private
		 */
		_onMulPulsatingDotClicked: function ( _event ) {
			if ( !this._popup ) {
				var $target = $( this.element ).find( '.mw-pulsating-dot-container' );

				var dontShowMulPopupCheckbox = new OO.ui.CheckboxInputWidget( {
					value: true,
					selected: false
				} ).on( 'change', function ( value ) {
					new mw.Api().saveOption( 'wb-dont-show-again-mul-popup', value ? '1' : null );
					mw.user.options.set( 'wb-dont-show-again-mul-popup', value ? '1' : null );
				} );

				var showAgainLayout = new OO.ui.FieldLayout( dontShowMulPopupCheckbox, {
					align: 'inline',
					label: mw.msg( 'wikibase-entityterms-languagelistview-mul-popup-dont-show-again' )
				} );

				var $tooltipContent = $( '<div>' ).append(
					mw.message( 'wikibase-entityterms-languagelistview-mul-popup-content' ).parseDom(),
					showAgainLayout.$element
				);

				this._popup = new OO.ui.PopupWidget( {
					padded: true,
					width: 400,
					head: true,
					label: mw.msg(
						'wikibase-entityterms-languagelistview-mul-popup-title'
					),
					$content: $tooltipContent,
					classes: [ 'wikibase-entityterms-languagelistview-mul-popup' ],
					$floatableContainer: $target,
					position: 'after',
					align: 'forwards'
				} );
				$( document.body ).append( this._popup.$element );
			}
			this._popup.toggle();
		},

		/**
		 * @param {string} lang
		 * @return {Object}
		 * @private
		 */
		_getValueForLanguage: function ( lang ) {
			var fingerprint = this.options.value;

			return {
				language: lang,
				label: fingerprint.getLabelFor( lang ) || new datamodel.Term( lang, '' ),
				description: fingerprint.getDescriptionFor( lang ) || new datamodel.Term( lang, '' ),
				aliases: fingerprint.getAliasesFor( lang ) || new datamodel.MultiTerm( lang, [] )
			};
		},

		_startEditing: function () {
			var self = this;
			var listview = this.$listview.data( 'listview' );
			return listview.startEditing().done( function () {
				self.updateInputSize();
			} );
		},

		/**
		 * @param {boolean} [dropValue]
		 */
		_stopEditing: function ( dropValue ) {
			var listview = this.$listview.data( 'listview' );

			return $.when.apply( $, listview.value().map( function ( entitytermsforlanguageview ) {
				return entitytermsforlanguageview.stopEditing( dropValue );
			} ) );
		},

		/**
		 * Updates the size of the input boxes by triggering the inputautoexpand plugin's `expand()`
		 * function.
		 */
		updateInputSize: function () {
			var listview = this.$listview.data( 'listview' ),
				lia = listview.listItemAdapter();

			listview.items().each( function () {
				var entitytermsforlanguageview = lia.liInstance( $( this ) );

				[ 'label', 'description', 'aliases' ].forEach( function ( name ) {
					var $view = entitytermsforlanguageview[ '$' + name + 'view' ],
						autoExpandInput = $view.find( 'input,textarea' ).data( 'inputautoexpand' );

					if ( autoExpandInput ) {
						autoExpandInput.options( {
							maxWidth: $view.width()
						} );
						autoExpandInput.expand( true );
					}
				} );
			} );
		},

		/**
		 * @see jQuery.ui.TemplatedWidget.focus
		 */
		focus: function () {
			var listview = this.$listview.data( 'listview' ),
				$items = listview.items();

			if ( $items.length ) {
				listview.listItemAdapter().liInstance( $items.first() ).focus();
			} else {
				this.element.trigger( 'focus' );
			}
		},

		removeError: function () {
			PARENT.prototype.removeError.call( this );

			var listview = this.$listview.data( 'listview' ),
				lia = listview.listItemAdapter();

			listview.items().each( function () {
				var entitytermsforlanguageview = lia.liInstance( $( this ) );
				entitytermsforlanguageview.removeError();
			} );
		},

		/**
		 * @param {datamodel.Fingerprint} [value]
		 * @return {datamodel.Fingerprint|*}
		 */
		value: function ( value ) {
			if ( value !== undefined ) {
				return this.option( 'value', value );
			}

			var listview = this.$listview.data( 'listview' ),
				lia = listview.listItemAdapter();

			// Clones the current Fingerprint.
			// FIXME: This accesses the private _items property since there is no copy or clone.
			value = new datamodel.Fingerprint(
				new datamodel.TermMap( this.options.value.getLabels()._items ),
				new datamodel.TermMap( this.options.value.getDescriptions()._items ),
				new datamodel.MultiTermMap( this.options.value.getAliases()._items )
			);

			// this only adds all terms visible in the ui to the Fingerprint, all other languages get ignored
			listview.items().each( function () {
				var terms = lia.liInstance( $( this ) ).value();
				if ( terms.label.getText() === '' ) {
					// FIXME: DataModel JavaScript should do this.
					value.removeLabelFor( terms.language );
				} else {
					value.setLabel( terms.language, terms.label );
				}
				if ( terms.description.getText() === '' ) {
					// FIXME: DataModel JavaScript should do this.
					value.removeDescriptionFor( terms.language );
				} else {
					value.setDescription( terms.language, terms.description );
				}
				if ( terms.aliases.isEmpty() ) {
					// FIXME: DataModel JavaScript should do this.
					value.removeAliasesFor( terms.language );
				} else {
					value.setAliases( terms.language, terms.aliases );
				}
			} );

			return value;
		},

		/**
		 * @see jQuery.ui.TemplatedWidget._setOption
		 * @return {jQuery.Widget}
		 */
		_setOption: function ( key, value ) {
			var response = PARENT.prototype._setOption.apply( this, arguments );

			if ( key === 'value' ) {
				var self = this;
				this.$listview.data( 'listview' ).value().forEach( function ( entitytermsforlanguageview ) {
					entitytermsforlanguageview.value( self._getValueForLanguage( entitytermsforlanguageview.value().language ) );
				} );
			}

			if ( key === 'disabled' ) {
				this.$listview.data( 'listview' ).option( key, value );
			}

			return response;
		},

		/**
		 * Adds a clickable pulsating dot into the language column for "mul", if we have a "mul" row.
		 *
		 * @private
		 */
		_addPulsatingDotToMul: function () {
			var $mulLanguageRow = this.element.find( '.wikibase-entitytermsforlanguageview-mul' );
			if ( $mulLanguageRow.length ) {
				var $pulsatingDot = $( '<span>' ).addClass( 'mw-pulsating-dot-container' );
				$pulsatingDot.append( $( '<a>' ).addClass( 'mw-pulsating-dot' ) )
					.on( 'click', this._onMulPulsatingDotClicked.bind( this ) );

				$mulLanguageRow.find( '.wikibase-entitytermsforlanguageview-language' ).append( $pulsatingDot );
			}
		}
	} );

}() );
