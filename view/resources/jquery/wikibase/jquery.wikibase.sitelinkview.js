/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget,
		datamodel = require( 'wikibase.datamodel' );

	require( './jquery.wikibase.siteselector.js' );

	/**
	 * Displays and allows editing a site link.
	 *
	 * @extends jQuery.ui.EditableTemplatedWidget
	 *
	 * @option {datamodel.SiteLink} [value]
	 *         Default: null
	 *
	 * @option {Function} [getAllowedSites]
	 *         Function returning an array of wikibase.Site objects.
	 *         Default: function() { return []; }
	 *
	 * @option {wikibase.entityIdFormatter.EntityIdPlainFormatter} entityIdPlainFormatter
	 *
	 * @event change
	 *        - {jQuery.Event}
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
	$.widget( 'wikibase.sitelinkview', PARENT, {
		options: {
			template: 'wikibase-sitelinkview',
			templateParams: [
				function () {
					var site = this._getSite();
					return site ? site.getId() : 'new';
				},
				function () {
					var site = this._getSite();
					return site ? site.getId() : '';
				},
				function () {
					var site = this._getSite();
					return site ? site.getShortName() : '';
				},
				'' // page name
			],
			templateShortCuts: {
				$siteIdContainer: '.wikibase-sitelinkview-siteid-container',
				$siteId: '.wikibase-sitelinkview-siteid',
				$link: '.wikibase-sitelinkview-link'
			},
			value: null,
			getAllowedSites: function () { return []; },
			entityIdPlainFormatter: null
		},

		/**
		 * @type {jQuery.wikibase.badgeselector|null}
		 */
		_badgeselector: null,

		/**
		 * @see jQuery.ui.TemplatedWidget._create
		 */
		_create: function () {
			if ( !this.options.entityIdPlainFormatter ) {
				throw new Error( 'Required option(s) missing' );
			}

			PARENT.prototype._create.call( this );

			if ( !this.$link.children().length ) {
				// sitelinkview is created dynamically, in contrast to being initialized on pre-existing
				// DOM.
				this._draw();
			}

			this._createBadgeSelector();
		},

		_createRemover: function () {
			this._siteLinkRemover = this.options.getSiteLinkRemover( this.$siteIdContainer, mw.msg( 'wikibase-remove' ) );
			this._siteLinkRemover[ this.options.value ? 'enable' : 'disable' ]();

			// Update inputautoexpand maximum width after adding "remove" toolbar:
			var $siteIdInput = this.$siteId.find( 'input' ),
				inputautoexpand = $siteIdInput.length
					? $siteIdInput.data( 'inputautoexpand' )
					: null;
			if ( inputautoexpand ) {
				$siteIdInput.inputautoexpand( {
					maxWidth: this.element.width() - (
						this.$siteIdContainer.outerWidth( true ) - $siteIdInput.width()
					)
				} );
			}

			this.updatePageNameInputAutoExpand();
		},

		/**
		 * @see jQuery.ui.TemplatedWidget.destroy
		 */
		destroy: function () {
			if ( this._badgeselector ) {
				this._badgeselector.destroy();
			}

			if ( this._siteLinkRemover ) {
				this._siteLinkRemover.destroy();
				this._siteLinkRemover = null;
			}

			if ( this.isInEditMode() ) {
				var self = this;

				this.element.one( this.widgetEventPrefix + 'afterstopediting', function ( event ) {
					PARENT.prototype.destroy.call( self );
				} );

				this.element.removeClass( 'wb-edit' );
			} else {
				PARENT.prototype.destroy.call( this );
			}
		},

		_createBadgeSelector: function () {
			var self = this,
				$badgeselector = this.$link.find( '.wikibase-sitelinkview-badges' ),
				badges = mw.config.get( 'wbBadgeItems' );

			if ( $.isEmptyObject( badges ) ) {
				return;
			}

			$badgeselector
			.badgeselector( {
				value: this.options.value ? this.options.value.getBadges() : [],
				badges: badges,
				entityIdPlainFormatter: this.options.entityIdPlainFormatter,
				isRtl: $( document.body ).hasClass( 'rtl' ),
				messages: {
					'badge-placeholder-title': mw.msg(
						'wikibase-badgeselector-badge-placeholder-title'
					)
				},
				encapsulate: true
			} )
			.on( 'badgeselectorchange', function ( event ) {
				// Adding/removing badges decreases/increases available space:
				self.updatePageNameInputAutoExpand();
				self._trigger( 'change' );
				self._siteLinkRemover[ self.value() === null ? 'disable' : 'enable' ]();
			} );

			this._badgeselector = $badgeselector.data( 'badgeselector' );
		},

		/**
		 * Main rendering function.
		 */
		_draw: function () {
			if ( !this.$link.children().length ) {
				var siteLink = this.options.value,
					site = this._getSite();

				this.$link.append(
					mw.wbTemplate( 'wikibase-sitelinkview-pagename',
						siteLink ? site.getUrlTo( siteLink.getPageName() ) : '',
						siteLink ? siteLink.getPageName() : '',
						mw.wbTemplate( 'wikibase-badgeselector', '' ),
						site ? site.getLanguageCode() : '',
						site ? site.getLanguageDirection() : ''
					)
				);
			}

			if ( !this._badgeselector ) {
				this._createBadgeSelector();
			}

			if ( this.isInEditMode() ) {
				this._drawEditMode();
			}
		},

		/**
		 * Draws the edit mode context.
		 */
		_drawEditMode: function () {
			var self = this,
				pageNameInputOptions = {},
				dir = $( document.documentElement ).prop( 'dir' );

			if ( this.options.value ) {
				pageNameInputOptions = {
					siteId: this.options.value.getSiteId(),
					pageName: this.options.value.getPageName()
				};

				var site = wb.sites.getSite( this.options.value.getSiteId() );
				if ( site ) {
					dir = site.getLanguageDirection();
				}
			}

			this._createRemover();

			var $pageNameInput = $( '<input>' )
				.attr( 'placeholder', mw.msg( 'wikibase-sitelink-page-edit-placeholder' ) )
				.attr( 'dir', dir )
				.pagesuggester( pageNameInputOptions );

			var pagesuggester = $pageNameInput.data( 'pagesuggester' );

			$pageNameInput
			.on( 'pagesuggesterchange.' + this.widgetName, function ( event ) {
				if ( !pagesuggester.isSearching() ) {
					self.setError();
					self._trigger( 'change' );
				}
				self._siteLinkRemover[ self.value() === null ? 'disable' : 'enable' ]();
			} );

			this.$link.find( '.wikibase-sitelinkview-page' )
				.attr( 'dir', dir )
				.empty().append( $pageNameInput );

			if ( this._badgeselector ) {
				this._badgeselector.startEditing();
			}

			if ( this.options.value ) {
				this.updatePageNameInputAutoExpand();
				// Site of an existing site link is not supposed to be changeable.
				return;
			}

			var $siteIdInput = $( '<input>' )
				// FIXME: "noime" class prevents Universal Language Selector's IME from being applied
				// to the input element with the IME overlaying the site suggestions (see T88417).
				.addClass( 'noime' )
				.attr( 'placeholder', mw.msg( 'wikibase-sitelink-site-edit-placeholder' ) )
				.siteselector( {
					source: self.options.getAllowedSites
				} );

			// Disable and hide initially and wait for valid site input:
			pagesuggester.disable();
			$pageNameInput.hide();

			if ( this._badgeselector
				&& ( !this.options.value || !this.options.value.getBadges().length )
			) {
				this._badgeselector.element.hide();
			}

			$siteIdInput
			.on( 'siteselectorselected.' + this.widgetName, function ( event, siteId ) {
				var selectedSite = wb.sites.getSite( siteId );

				if ( selectedSite ) {
					$pageNameInput
					.attr( 'lang', selectedSite.getLanguageCode() )
					.attr( 'dir', selectedSite.getLanguageDirection() )
					.show();
				} else {
					$pageNameInput.hide();
				}

				if ( self._badgeselector ) {
					self._badgeselector.element[ selectedSite ? 'show' : 'hide' ]();
				}

				pagesuggester[ selectedSite ? 'enable' : 'disable' ]();
				pagesuggester.option( 'siteId', siteId );

				self._trigger( 'change' );
				self._siteLinkRemover[ self.value() === null ? 'disable' : 'enable' ]();
			} )
			.on(
				'siteselectorselected.' + this.widgetName + ' siteselectorchange.' + this.widgetName,
				function ( event, siteId ) {
					var inputautoexpand = $siteIdInput.data( 'inputautoexpand' );

					if ( inputautoexpand ) {
						inputautoexpand.expand();
					}

					self.updatePageNameInputAutoExpand();
				}
			);

			this.$siteId.append( $siteIdInput );

			$siteIdInput.inputautoexpand( {
				maxWidth: this.element.width() - (
					this.$siteIdContainer.outerWidth( true ) - $siteIdInput.width()
				)
			} );

			this.updatePageNameInputAutoExpand();

			$pageNameInput
			.on( 'keydown.' + this.widgetName, function ( event ) {
				if ( event.keyCode === $.ui.keyCode.BACKSPACE && $pageNameInput.val() === '' ) {
					event.stopPropagation();
					$siteIdInput.val( '' ).trigger( 'focus' );
					$siteIdInput.data( 'siteselector' ).setSelectedSite( null );
				}
			} );
		},

		/**
		 * Updates the maximum width the page name input element may grow to.
		 */
		updatePageNameInputAutoExpand: function () {
			var $pageNameInput = this.$link.find( 'input' );

			if ( !$pageNameInput.length ) {
				return;
			}

			$pageNameInput.inputautoexpand( {
				maxWidth: Math.floor( this.element.width()
					- this.$siteIdContainer.outerWidth( true )
					- ( this.$link.outerWidth( true ) - $pageNameInput.width() ) )
			} );

			$pageNameInput.data( 'inputautoexpand' ).expand( true );
		},

		/**
		 * @return {boolean}
		 */
		isEmpty: function () {
			if ( !this.isInEditMode() ) {
				return !this.options.value;
			}

			return !this.options.value
				&& this.$link.find( 'input' ).val().trim() === ''
				&& this.$siteId.find( 'input' ).val().trim() === '';
		},

		/**
		 * Puts the widget into edit mode.
		 */
		_startEditing: function () {
			this._draw();

			if ( this.option( 'disabled' ) ) {
				this._setState( 'disable' );
			}

			return $.Deferred().resolve().promise();
		},

		/**
		 * Stops the widget's edit mode.
		 *
		 * @param {boolean} dropValue
		 * @return {Object} jQuery.Promise
		 *         Resolved parameters:
		 *         - {boolean} dropValue
		 *         Rejected parameters:
		 *         - {Error}
		 */
		_stopEditing: function ( dropValue ) {
			if ( this._badgeselector ) {
				this._badgeselector.stopEditing( dropValue );
			}

			return $.Deferred().resolve().promise();
		},

		/**
		 * @return {wikibase.Site|null}
		 */
		_getSite: function () {
			var siteLink = this.value();
			return siteLink ? wb.sites.getSite( siteLink.getSiteId() ) : null;
		},

		/**
		 * Sets/Gets the widget's value.
		 *
		 * @param {datamodel.SiteLink|null} [siteLink]
		 * @return {datamodel.SiteLink|undefined}
		 */
		value: function ( siteLink ) {
			if ( siteLink === undefined ) {
				if ( !this.isInEditMode() ) {
					return this.options.value;
				}

				var siteselector = this.element.find( ':wikibase-siteselector' ).data( 'siteselector' ),
					$pagesuggester = this.element.find( ':wikibase-pagesuggester' ),
					siteId;

				if ( siteselector ) {
					var site = siteselector.getSelectedSite();
					siteId = site ? site.getId() : null;
				} else {
					siteId = this.options.value ? this.options.value.getSiteId() : null;
				}

				// TODO: Do not allow null values for siteId and pageName in datamodel.SiteLink
				if ( !siteId || $pagesuggester.val() === '' ) {
					return null;
				}

				return new datamodel.SiteLink(
					siteId,
					$pagesuggester.val(),
					this._badgeselector ? this._badgeselector.value() : []
				);
			} else if ( !( siteLink instanceof datamodel.SiteLink ) ) {
				throw new Error( 'Value needs to be a SiteLink instance' );
			}

			return this.option( 'value', siteLink );
		},

		/**
		 * @see jQuery.ui.TemplatedWidget._setOption
		 *
		 * @throws {Error} when trying to set a site link with a new site id.
		 */
		_setOption: function ( key, value ) {
			if ( key === 'value'
				&& this.options.value
				&& value.getSiteId() !== this.options.value.getSiteId()
			) {
				throw new Error( 'Cannot set site link with new site id after initialization' );
			}

			var response = PARENT.prototype._setOption.apply( this, arguments );

			if ( key === 'value' ) {
				this._draw();
			} else if ( key === 'disabled' ) {
				this._setState( value ? 'disable' : 'enable' );
			}

			return response;
		},

		/**
		 * @param {string} state
		 */
		_setState: function ( state ) {
			if ( this.isInEditMode() ) {
				var $siteInput = this.$siteId.find( 'input' ),
					hasSiteId = !!( this.options.value && this.options.value.getSiteId() );

				if ( $siteInput.length ) {
					var siteselector = $siteInput.data( 'siteselector' );
					hasSiteId = !!siteselector.getSelectedSite();
					siteselector[ state ]();
				}

				if ( this._siteLinkRemover ) {
					this._siteLinkRemover[ state ]();
				}

				// Do not enable page input if no site is set:
				if ( state === 'disable' || hasSiteId ) {
					this.$link.find( 'input' ).data( 'pagesuggester' )[ state ]();
					if ( this._badgeselector ) {
						this._badgeselector[ state ]();
					}
				}
			}
		},

		/**
		 * @see jQuery.ui.TemplatedWidget.focus
		 */
		focus: function () {
			var $siteselector = this.element.find( ':wikibase-siteselector' ),
				$pagesuggester = this.element.find( ':wikibase-pagesuggester' );

			if ( $pagesuggester.length
				&& !$pagesuggester.data( 'pagesuggester' ).option( 'disabled' )
			) {
				$pagesuggester.trigger( 'focus' );
			} else if ( $siteselector.length ) {
				$siteselector.trigger( 'focus' );
			} else {
				this.element.trigger( 'focus' );
			}
		}

	} );

}( wikibase ) );
