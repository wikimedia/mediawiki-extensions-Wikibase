/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget,
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * Encapsulates a entitytermsforlanguagelistview widget.
	 *
	 * @extends jQuery.ui.EditableTemplatedWidget
	 *
	 * @option {Datamodel.fingerprint} value
	 *
	 * @option {string[]} userLanguages
	 *         A list of languages for which terms should be displayed initially.
	 *
	 * @option {string} [helpMessage]
	 *                  Default: 'Edit label, description and aliases per language.'
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
	$.widget( 'wikibase.entitytermsview', PARENT, {
		options: {
			template: 'wikibase-entitytermsview',
			templateParams: [
				function () {
					return $( mw.wbTemplate(
						'wikibase-entitytermsview-heading-part',
						'description',
						'',
						''
					) ).add( mw.wbTemplate(
						'wikibase-entitytermsview-heading-part',
						'aliases',
						'',
						''
					) );
				}, // header content
				'', // entitytermsforlanguagelistview
				'', // additional entitytermsforlanguagelistview container class(es)
				'' // toolbar placeholder
			],
			templateShortCuts: {
				$headingDescription: '.wikibase-entitytermsview-heading-description',
				$entitytermsforlanguagelistviewContainer:
					'.wikibase-entitytermsview-entitytermsforlanguagelistview'
			},
			value: null,
			userLanguages: [],
			helpMessage: 'Edit label, description and aliases per language.'
		},

		/**
		 * @type {jQuery}
		 */
		$entitytermsforlanguagelistview: null,

		/**
		 * @type {jQuery}
		 */
		$entitytermsforlanguagelistviewToggler: null,

		/**
		 * @type {jQuery|null}
		 */
		$entitytermsforlanguagelistviewHelp: null,

		/**
		 * @type {Object} Has the termbox been hidden or shown via the button and has this click been tracked?
		 */
		_tracked: {},

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

			var self = this;

			this.element
			.on(
				this.widgetEventPrefix + 'change.' + this.widgetName + ' ' +
				this.widgetEventPrefix + 'afterstopediting.' + this.widgetName,
				function ( event, lang ) {
					var firstLanguage = self.options.userLanguages[ 0 ];

					if ( typeof lang === 'string' && lang !== firstLanguage ) {
						return;
					}

					var fingerprint = self.value(),
						description = fingerprint.getDescriptionFor( firstLanguage ),
						aliases = fingerprint.getAliasesFor( firstLanguage ),
						isDescriptionEmpty = !description || description.getText() === '',
						isAliasesEmpty = !aliases || aliases.isEmpty();

					self.$headingDescription
						.toggleClass( 'wb-empty', isDescriptionEmpty )
						.text( isDescriptionEmpty
							? mw.msg( 'wikibase-description-empty' )
							: description.getText()
						);

					var $ul = self.element.find( '.wikibase-entitytermsview-heading-aliases' )
						.toggleClass( 'wb-empty', isAliasesEmpty )
						.children( 'ul' );

					if ( isAliasesEmpty ) {
						$ul.remove();
					} else {
						if ( $ul.length === 0 ) {
							$ul = mw.wbTemplate( 'wikibase-entitytermsview-aliases', '' );
							self.element.find( '.wikibase-entitytermsview-heading-aliases' ).append( $ul );
						}
						$ul.empty();
						aliases.getTexts().forEach( function ( text ) {
							$ul.append( mw.wbTemplate(
								'wikibase-entitytermsview-aliases-alias',
								text,
								mw.msg( 'wikibase-aliases-separator' )
							) );
						} );
					}
				}
			);

			this.draw();
		},

		/**
		 * @see jQuery.ui.TemplatedWidget.destroy
		 */
		destroy: function () {
			// When destroying a widget not initialized properly, entitytermsforlanguagelistview will
			// not have been created.
			if ( this.$entitytermsforlanguagelistview ) {
				var entitytermsforlanguagelistview = this._getEntitytermsforlanguagelistview();

				if ( entitytermsforlanguagelistview ) {
					entitytermsforlanguagelistview.destroy();
				}

				this.$entitytermsforlanguagelistview.remove();
			}

			if ( this.$entitytermsforlanguagelistviewToggler ) {
				this.$entitytermsforlanguagelistviewToggler.remove();
			}

			if ( this.$entitytermsforlanguagelistviewHelp ) {
				this.$entitytermsforlanguagelistviewHelp.remove();
			}

			this.element.off( '.' + this.widgetName );
			this.element.removeClass( 'wikibase-entitytermsview' );
			PARENT.prototype.destroy.call( this );
		},

		/**
		 * @inheritdoc
		 */
		draw: function () {
			var deferred = $.Deferred();

			this.$entitytermsforlanguagelistview
				= this.element.find( '.wikibase-entitytermsforlanguagelistview' );

			if ( !this.$entitytermsforlanguagelistview.length ) {
				this.$entitytermsforlanguagelistview = $( '<div>' )
					.appendTo( this.$entitytermsforlanguagelistviewContainer );
			}

			if ( !this._getEntitytermsforlanguagelistview() ) {
				this._createEntitytermsforlanguagelistview();
			}

			if ( !this.element
				.find( '.wikibase-entitytermsview-entitytermsforlanguagelistview-toggler' )
				.length
			) {
				// TODO: Remove as soon as drop-down edit buttons are implemented. The language list may
				// then be shown (without directly switching to edit mode) using the drop down menu.
				this._createEntitytermsforlanguagelistviewToggler();
			}

			return deferred.resolve().promise();
		},

		_trackToggling: function ( isVisible ) {
			var currentActionTracking = isVisible ? 'hide' : 'show';
			if ( this._tracked[ currentActionTracking ] ) {
				return;
			}
			mw.track(
				'event.WikibaseTermboxInteraction', {
					actionType: currentActionTracking
				}
			);
			this._tracked[ currentActionTracking ] = true;
		},

		/**
		 * Creates the dedicated toggler for showing/hiding the list of entity terms. This function is
		 * supposed to be removed as soon as drop-down edit buttons are implemented with the mechanism
		 * toggling the list's visibility while not starting edit mode will be part of the drop-down
		 * menu.
		 *
		 * @private
		 */
		_createEntitytermsforlanguagelistviewToggler: function () {
			var self = this,
				api = new mw.Api();

			this.$entitytermsforlanguagelistviewToggler = $( '<div>' )
				.addClass( 'wikibase-entitytermsview-entitytermsforlanguagelistview-toggler' )
				.text( mw.msg( 'wikibase-entitytermsview-entitytermsforlanguagelistview-toggler' ) )
				.toggler( {
					$subject: this.$entitytermsforlanguagelistviewContainer,
					duration: 'fast'
				} )
				.on( 'toggleranimation.' + this.widgetName, function ( event, params ) {
					if ( mw.user.isAnon() ) {
						mw.cookie.set(
							'wikibase-entitytermsview-showEntitytermslistview',
							params.visible,
							{ expires: 365 * 24 * 60 * 60, path: '/' }
						);
					} else {
						api.saveOption(
							'wikibase-entitytermsview-showEntitytermslistview',
							params.visible ? '1' : '0'
						)
						.done( function () {
							mw.user.options.set(
								'wikibase-entitytermsview-showEntitytermslistview',
								params.visible ? '1' : '0'
							);
						} );
					}

					// Show "help" link only if the toggler content is visible (decided by Product
					// Management):
					if ( self.$entitytermsforlanguagelistviewHelp ) {
						self.$entitytermsforlanguagelistviewHelp.toggleClass(
							'wikibase-entitytermsview-entitytermsforlanguagelistview-configure-hidden',
							!params.visible
						);
					}

					self._trackToggling( params.visible );
				} );

			this.$entitytermsforlanguagelistviewContainer.before(
				this.$entitytermsforlanguagelistviewToggler
			);

			// Inject link to page providing help about how to configure languages:
			// TODO: Remove as soon as soon as some user-friendly mechanism is implemented to define
			// user languages.

			if ( mw.config.get( 'wbUserSpecifiedLanguages' )
				&& mw.config.get( 'wbUserSpecifiedLanguages' ).length > 1
			) {
				// User applied custom configuration, no need to show link to help page.
				return;
			}

			var toggler = this.$entitytermsforlanguagelistviewToggler.data( 'toggler' );

			this.$entitytermsforlanguagelistviewHelp =
				$( '<span>' )
				.addClass( 'wikibase-entitytermsview-entitytermsforlanguagelistview-configure' )
				.append(
					$( '<a>' )
					.attr(
						'href',
						mw.msg(
							'wikibase-entitytermsview-entitytermsforlanguagelistview-configure-link'
						)
					)
					.text( mw.msg(
						'wikibase-entitytermsview-entitytermsforlanguagelistview-configure-link-label'
					) )
				)
				.insertAfter( this.$entitytermsforlanguagelistviewToggler );

			if ( !toggler.option( '$subject' ).is( ':visible' ) ) {
				this.$entitytermsforlanguagelistviewHelp
					.addClass(
						'wikibase-entitytermsview-entitytermsforlanguagelistview-configure-hidden'
					);
			}
		},

		/**
		 * @return {jQuery.wikibase.entitytermsforlanguagelistview}
		 * @private
		 */
		_getEntitytermsforlanguagelistview: function () {
			return this.$entitytermsforlanguagelistview.data( 'entitytermsforlanguagelistview' );
		},

		/**
		 * Creates and initializes the entitytermsforlanguagelistview widget.
		 */
		_createEntitytermsforlanguagelistview: function () {
			var self = this,
				prefix = $.wikibase.entitytermsforlanguagelistview.prototype.widgetEventPrefix;

			this.$entitytermsforlanguagelistview
			.on( prefix + 'change.' + this.widgetName, function ( event, lang ) {
				event.stopPropagation();
				// Event handlers for this are in the entitytermsview toolbar controller (for enabling
				// the save button), in entityViewInit (for updating the title) and in this file (for
				// updating description and aliases).
				self._trigger( 'change', null, [ lang ] );
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
			)
			.entitytermsforlanguagelistview( {
				value: this.options.value,
				userLanguages: this.options.userLanguages
			} );
		},

		_startEditing: function () {
			this._getEntitytermsforlanguagelistview().startEditing();
			return this.draw();
		},

		/**
		 * @param {boolean} dropValue
		 */
		_stopEditing: function ( dropValue ) {
			this.draw();
			var self = this;
			return this._getEntitytermsforlanguagelistview().stopEditing( dropValue ).done( function () {
				self.notification();
			} );
		},

		/**
		 * @inheritdoc
		 */
		focus: function () {
			this._getEntitytermsforlanguagelistview().focus();
		},

		/**
		 * @inheritdoc
		 */
		removeError: function () {
			this.element.removeClass( 'wb-error' );
			this._getEntitytermsforlanguagelistview().removeError();
		},

		/**
		 * @inheritdoc
		 *
		 * @param {Datamodel.fingerprint} [value]
		 * @return {Fingerprint|*}
		 */
		value: function ( value ) {
			if ( value !== undefined ) {
				return this.option( 'value', value );
			}

			return this._getEntitytermsforlanguagelistview().value();
		},

		/**
		 * @inheritdoc
		 */
		_setOption: function ( key, value ) {
			if ( key === 'value' && !( value instanceof datamodel.Fingerprint ) ) {
				throw new Error( 'value must be a Fingerprint' );
			}

			var response = PARENT.prototype._setOption.apply( this, arguments );

			if ( key === 'disabled' || key === 'value' ) {
				this._getEntitytermsforlanguagelistview().option( key, value );
			}

			return response;
		},

		/**
		 * @inheritdoc
		 */
		notification: function ( $content, additionalCssClasses ) {
			if ( !this._$notification ) {
				var $closeable = $( '<div>' ).closeable();

				this._$notification = $( '<tr>' ).append( $( '<td>' ).append( $closeable ) );

				this._$notification.data( 'closeable', $closeable.data( 'closeable' ) );
				this._$notification
					.appendTo( this._getEntitytermsforlanguagelistview().$header );

				var $headerTr = this._getEntitytermsforlanguagelistview().$header.children( 'tr' ).first();
				this._$notification.children( 'td' ).attr( 'colspan', $headerTr.children().length );

			}

			this._$notification.data( 'closeable' ).setContent( $content, additionalCssClasses );
			return this._$notification;
		},

		/**
		 * @inheritdoc
		 */
		setError: function ( error ) {
			if ( error && error.context ) {
				var context = error.context;
				var viewType = 'wikibase-' + context.type + 'view';
				this.element.find( '.wikibase-entitytermsforlanguageview-' + context.value.getLanguageCode() )
					.find( '.' + viewType ).data( viewType ).setError( error );
			}
			return PARENT.prototype.setError.apply( this, arguments );
		}
	} );

}() );
