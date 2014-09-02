/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * Displays and allows editing a site link.
 * @since 0.5
 * @extends jQuery.ui.TemplatedWidget
 *
 * @option {wikibase.datamodel.SiteLink} [value]
 *         Default: null
 *
 * @option {string[]} [allowedSiteIds]
 *         Default: []
 *
 * @option {wikibase.store.EntityStore} entityStore
 *
 * @option {string} [helpMessage]
 *         Default: mw.msg( 'wikibase-sitelinks-input-help-message' )
 *
 * @event change
 *        - {jQuery.Event}
 *
 * @event afterstartediting
 *       - [jQuery.Event}
 *
 * @event stopediting
 *        - {jQuery.Event}
 *        - {boolean} Whether to drop the value.
 *        - {Function} Callback function.
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
			function() {
				var site = this._getSite();
				return site ? site.getId() : 'new';
			},
			function() {
				var site = this._getSite();
				return site ? site.getLanguageCode() : '';
			},
			function() {
				var site = this._getSite();
				return site ? site.getLanguageDirection() : '';
			},
			function() {
				var site = this._getSite();
				return site ? site.getShortName() : '';
			},
			function() {
				var site = this._getSite();
				return site ? site.getId() : '';
			},
			'', // page name
			'' // toolbar
		],
		templateShortCuts: {
			'$siteName': '.wikibase-sitelinkview-sitename',
			'$siteId' : '.wikibase-sitelinkview-siteid',
			'$link': '.wikibase-sitelinkview-link'
		},
		value: null,
		allowedSiteIds: [],
		entityStore: null,
		helpMessage: mw.msg( 'wikibase-sitelinks-input-help-message' )
	},

	/**
	 * @type {boolean}
	 */
	_isInEditMode: false,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		if( !this.options.entityStore || !this.options.helpMessage ) {
			throw new Error( 'Required option(s) missing' );
		}

		PARENT.prototype._create.call( this );

		if( !this.$link.children().length ) {
			// sitelinkview is created dynamically, in contrast to being initialized on pre-existing
			// DOM.
			this._draw();
		}
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		if( this._isInEditMode ) {
			var self = this;

			this.element.one( this.widgetEventPrefix + 'afterstopediting', function( event ) {
				PARENT.prototype.destroy.call( self );
			} );

			this.cancelEditing();
		} else {
			PARENT.prototype.destroy.call( this );
		}
	},

	/**
	 * Main rendering function.
	 */
	_draw: function() {
		if( this._isInEditMode ) {
			this.element.addClass( 'wb-edit' );
			this._drawEditMode();
		} else {
			this.element.removeClass( 'wb-edit' );

			var siteLink = this.options.value,
				site = this._getSite();

			this.$link.empty().append(
				mw.template( 'wikibase-sitelinkview-pagename',
					siteLink ? site.getUrlTo( siteLink.getPageName() ) : '',
					siteLink ? siteLink.getPageName() : '',
					'', // badges
					siteLink ? site.getLanguageCode() : '',
					siteLink ? site.getLanguageDirection() : 'auto'
				)
			);
		}

		if( this.options.value ) {
			this._drawBadges();
		}
	},

	/**
	 * Draws the edit mode context.
	 */
	_drawEditMode: function() {
		var self = this,
			pageNameInputOptions = {};

		if( this.options.value ) {
			pageNameInputOptions = {
				siteId: this.options.value.getSiteId(),
				pageName: this.options.value.getPageName()
			};
		}

		var $pageNameInput = $( '<input/>' )
			.attr( 'placeholder', mw.msg( 'wikibase-sitelink-page-edit-placeholder' ) )
			.pagesuggester( pageNameInputOptions );

		$pageNameInput
		.on( 'eachchange.' + this.widgetName + ' pagesuggesterchange.' + this.widgetName,
			function( event ) {
				self._trigger( 'change' );
			}
		);

		this.$link.find( '.wikibase-sitelinkview-page' ).empty().append( $pageNameInput );

		if( this.options.value ) {
			// Site of an existing site link is not supposed to be changeable.
			return;
		}

		var $siteIdInput = $( '<input/>' )
			.attr( 'placeholder', mw.msg( 'wikibase-sitelink-site-edit-placeholder' ) )
			.siteselector( {
				source: $.map( this.option( 'allowedSiteIds' ), function( siteId ) {
					return wb.sites.getSite( siteId );
				} )
			} );

		var pagesuggester = $pageNameInput.data( 'pagesuggester' );

		// Disable initially and wait for valid site input:
		pagesuggester.disable();

		$siteIdInput
		.on( 'siteselectorselected.' + this.widgetName, function( event, siteId ) {
			var site = wb.sites.getSite( siteId );

			if( site ) {
				$pageNameInput
				.attr( 'lang', site.getLanguageCode() )
				.attr( 'dir', site.getLanguageDirection() );
			}

			pagesuggester[site ? 'enable' : 'disable']();
			pagesuggester.option( 'siteId', siteId );

			self._trigger( 'change' );
		} );

		this.$siteName.remove();

		this.$siteId
		.attr( 'colspan', '2' )
		.append( $siteIdInput );
	},

	/**
	 * Draws the site link's badges.
	 */
	_drawBadges: function() {
		var self = this,
			$container = this.$link.find( '.wikibase-sitelinkview-badges' ).empty();

		if( this._isInEditMode ) {
			var url = this._getBadgesLinkUrl();
			if( url ) {
				$container = $( '<a/>' ).attr( 'href', url ).appendTo( $container );
			}
		}

		var badges = this.options.value.getBadges();

		if( !badges.length ) {
			if( !this._isInEditMode ) {
				return;
			}

			$container.append(
				mw.template( 'wb-badge', 'empty', mw.msg( 'wikibase-add-badges' ) )
			);
			return;
		}

		$.each( badges, function( i, badgeItemId ) {
			var cssClasses = badgeItemId,
				customCssClasses = mw.config.get( 'wbBadgeItems' )[badgeItemId];

			if( customCssClasses ) {
				cssClasses += ' ' + customCssClasses;
			}

			self.option( 'entityStore' ).get( badgeItemId )
			.done( function( fetchedContent ) {
				var title = fetchedContent.getContent().getLabel() || badgeItemId;

				$container.append( mw.template( 'wb-badge', cssClasses, title ) );
			} );
		} );
	},

	/**
	 * Get the URL for editing the site link on Special:SetSiteLink.
	 *
	 * @return {string|null}
	 */
	_getBadgesLinkUrl: function() {
		var entityId,
			siteLink = this.options.value;

		try {
			entityId = this.element.closest( '.wikibase-entityview' ).attr( 'id' ).split( '-' ).pop();
		} catch( e ) {
			entityId = null;
		}

		if ( !entityId || !siteLink ) {
			return null;
		}

		return mw.util.getUrl( 'Special:SetSiteLink/' + entityId + '/' + siteLink.getSiteId() );
	},

	/**
	 * @return {boolean}
	 */
	isValid: function() {
		return !!this.value();
	},

	/**
	 * @return {boolean}
	 */
	isInitialValue: function() {
		var currentValue = this.value();

		if( !this.options.value || !currentValue ) {
			return false;
		}

		return currentValue.equals( this.options.value );
	},

	/**
	 * Puts the widget into edit mode.
	 */
	startEditing: function() {
		if( this._isInEditMode ) {
			return;
		}

		this._isInEditMode = true;
		this._draw();

		this._trigger( 'afterstartediting' );
	},

	/**
	 * Stops the widget's edit mode.
	 *
	 * @param {boolean} dropValue
	 */
	stopEditing: function( dropValue ) {
		var self = this;

		if( !this._isInEditMode || ( !this.isValid() || this.isInitialValue() ) && !dropValue ) {
			return;
		}

		this._trigger( 'stopediting', null, [dropValue, function() {
			self._afterStopEditing( dropValue );
		}] );
	},

	/**
	 * Cancels editing.
	 */
	cancelEditing: function() {
		this.stopEditing( true );
	},

	/**
	 * Callback tearing down edit mode.
	 *
	 * @param {boolean} dropValue
	 */
	_afterStopEditing: function( dropValue ) {
		if( !dropValue ) {
			this.options.value = this.value();
		}

		this._isInEditMode = false;
		this._draw();

		this._trigger( 'afterstopediting', null, [dropValue] );
	},

	/**
	 * @return {wikibase.Site|null}
	 */
	_getSite: function() {
		var siteLink = this.value();
		return siteLink ? wb.sites.getSite( siteLink.getSiteId() ) : null;
	},

	/**
	 * Sets/Gets the widget's value.
	 *
	 * @param {wikibase.datamodel.SiteLink|null} [siteLink]
	 * @return {wikibase.datamodel.SiteLink|undefined}
	 */
	value: function( siteLink ) {
		if( siteLink === undefined ) {
			if( !this._isInEditMode ) {
				return this.option( 'value' );
			}

			var siteselector = this.element.find( ':wikibase-siteselector' ).data( 'siteselector' ),
				$pagesuggester = this.element.find( ':wikibase-pagesuggester' ),
				siteId;

			if( siteselector ) {
				var site = siteselector.getSelectedSite();
				siteId = site ? site.getId() : null;
			} else {
				siteId = this.options.value.getSiteId();
			}

			// TODO: Do not allow null values for siteId and pageName in wikibase.datamodel.SiteLink
			if( !siteId || $pagesuggester.val() === '' ) {
				return null;
			}

			return new wb.datamodel.SiteLink( siteId, $pagesuggester.val() );

		} else if( !( siteLink instanceof wb.datamodel.SiteLink ) ) {
			throw new Error( 'Value needs to be a SiteLink instance' );
		}

		return this.option( 'value', siteLink );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 *
	 * @throws {Error} when trying to set a site link with a new site id.
	 */
	_setOption: function( key, value ) {
		if(
			key === 'value'
			&& this.options.value
			&& value.getSiteId() !== this.options.value.getSiteId()
		) {
			throw new Error( 'Cannot set site link with new site id after initialization' );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'value' ) {
			this._draw();
		} else if( key === 'disabled' ) {
			this._setState( value ? 'disable' : 'enable' );
		}

		return response;
	},

	/**
	 * @param {string} state
	 */
	_setState: function( state ) {
		if( this._isInEditMode ) {
			var $siteInput = this.$siteId.find( 'input' ),
				hasSiteId = !!( this.options.value && this.options.value.getSiteId() );

			if( $siteInput.length ) {
				var siteselector = $siteInput.data( 'siteselector' );
				hasSiteId = !!siteselector.getSelectedSite();
				siteselector[state]();
			}

			// Do not enable page input if no site is set:
			if( state === 'disable' || hasSiteId ) {
				this.$link.find( 'input' ).data( 'pagesuggester' )[state]();
			}
		}
	},

	/**
	 * Sets keyboard focus on the first input element.
	 */
	focus: function() {
		var $siteselector = this.element.find( ':wikibase-siteselector' ),
			$pagesuggester = this.element.find( ':wikibase-pagesuggester' );

		if( $siteselector.length ) {
			$siteselector.focus();
		} else if( $pagesuggester.length ) {
			$pagesuggester.focus();
		}
	},

	/**
	 * Applies/Removes error state.
	 *
	 * @param {Error} [error]
	 */
	setError: function( error ) {
		if( error ) {
			this.element.addClass( 'wb-error' );
			this._trigger( 'toggleerror', null, [error] );
		} else {
			this.element.removeClass( 'wb-error' );
			this._trigger( 'toggleerror' );
		}
	}

} );

$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'sitelinkview',
	selector: ':' + $.wikibase.sitelinkview.prototype.namespace
		+ '-' + $.wikibase.sitelinkview.prototype.widgetName,
	events: {
		sitelinkviewcreate: function( event, toolbarcontroller ) {
			var $sitelinkview = $( event.target ),
				sitelinkview = $sitelinkview.data( 'sitelinkview' );

			$sitelinkview.edittoolbar( {
				$container: $( '<td/>' ).appendTo( $sitelinkview ),
				interactionWidgetName: $.wikibase.sitelinkview.prototype.widgetName,
				parentWidgetFullName: 'wikibase.sitelinklistview',
				enableRemove: !!sitelinkview.value()
			} );

			$sitelinkview.on( 'keyup', function( event ) {
				if( sitelinkview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					sitelinkview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					sitelinkview.stopEditing( false );
				}
			} );

			$sitelinkview.one( 'toolbareditgroupedit', function() {

				toolbarcontroller.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					sitelinkview.widgetEventPrefix + 'change',
					function( event ) {
						var $sitelinkview = $( event.target ),
							sitelinkview = $sitelinkview.data( 'sitelinkview' ),
							toolbar = $sitelinkview.data( 'edittoolbar' ).toolbar,
							$btnSave = toolbar.editGroup.getButton( 'save' ),
							btnSave = $btnSave.data( 'toolbarbutton' ),
							enable = sitelinkview.isValid() && !sitelinkview.isInitialValue();

						btnSave[enable ? 'enable' : 'disable']();
					}
				);

			} );
		},
		sitelinkviewdisable: function( event ) {
			var $sitelinkview = $( event.target ),
				sitelinkview = $sitelinkview.data( 'sitelinkview' ),
				toolbar = $sitelinkview.data( 'edittoolbar' ).toolbar,
				$btnSave = toolbar.editGroup.getButton( 'save' ),
				btnSave = $btnSave.data( 'toolbarbutton' ),
				enable = sitelinkview.isValid() && !sitelinkview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
		},
		toolbareditgroupedit: function( event, toolbarcontroller ) {
			var $sitelinkview = $( event.target ).closest( ':wikibase-edittoolbar' ),
				sitelinkview = $sitelinkview.data( 'sitelinkview' );

			if( !sitelinkview ) {
				return;
			}

			sitelinkview.focus();
		}
	}
} );

}( mediaWiki, wikibase, jQuery ) );
