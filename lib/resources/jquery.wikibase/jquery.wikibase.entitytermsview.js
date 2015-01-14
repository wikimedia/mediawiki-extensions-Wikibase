/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, $ ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

/**
 * Encapsulates a entitytermsforlanguagelistview widget.
 * @since 0.5
 * @extends jQuery.ui.EditableTemplatedWidget
 *
 * @option {Object[]} value
 *         Object representing the widget's value.
 *         Structure: [
 *           {
 *             language: <{string]>,
 *             label: <{wikibase.datamodel.Term}>,
 *             description: <{wikibase.datamodel.Term}>
 *             aliases: <{wikibase.datamodel.MultiTerm}>
 *           }[, ...]
 *         ]
 *
 * @option {string} entityId
 *
 * @option {wikibase.entityChangers.EntityChangersFactory} entityChangersFactory
 *
 * @option {string} [helpMessage]
 *                  Default: 'Edit label, description and aliases per language.'
 *
 * @event change
 *        - {jQuery.Event}
 *
 * @event afterstartediting
 *       - {jQuery.Event}
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
$.widget( 'wikibase.entitytermsview', PARENT, {
	options: {
		template: 'wikibase-entitytermsview',
		templateParams: [
			'', // label class
			'', // labelview
			'', // aliases class
			'', // aliasesview
			'', // description class
			'', // descriptionview
			'', // entitytermsforlanguagelistview
			'', // additional entitytermsforlanguagelistview container class(es)
			'' // toolbar placeholder
		],
		templateShortCuts: {
			$headingLabel: '.wikibase-entitytermsview-heading-label',
			$headingAliases: '.wikibase-entitytermsview-heading-aliases',
			$headingDescription: '.wikibase-entitytermsview-heading-description',
			$entitytermsforlanguagelistviewContainer:
				'.wikibase-entitytermsview-entitytermsforlanguagelistview'
		},
		value: [],
		entityId: null,
		entityChangersFactory: null,
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
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		if(
			!$.isArray( this.options.value )
			|| !this.options.entityId
			|| !this.options.entityChangersFactory
		) {
			throw new Error( 'Required option(s) missing' );
		}

		PARENT.prototype._create.call( this );

		var self = this;

		this.element
		.on(
			this.widgetEventPrefix + 'change.' + this.widgetName
				+ ' ' + this.widgetEventPrefix + 'afterstopediting.' + this.widgetName,
			function() {
				$.each( self.value(), function() {
					if( this.language !== self.options.value[0].language ) {
						return true;
					}

					var $labelChildren = self.$headingLabel.children(),
						labelText = this.label.getText(),
						descriptionText = this.description.getText();

					self.$headingLabel
						.toggleClass( 'wb-empty', labelText === '' )
						.text( labelText === '' ? mw.msg( 'wikibase-label-empty' ) : labelText )
						.append( $labelChildren );

					self.$headingDescription
						.toggleClass( 'wb-empty', labelText === '' )
						.text( descriptionText === ''
							? mw.msg( 'wikibase-description-empty' )
							: descriptionText
						);

					var aliasesTexts = this.aliases.getTexts(),
						$ul = self.$headingAliases.children( 'ul' ).empty();

					for( var i = 0; i < aliasesTexts.length; i++ ) {
						$ul.append(
							mw.wbTemplate( 'wikibase-entitytermsview-aliases-alias',
								aliasesTexts[i]
							)
						);
					}

					return false;
				} );
			}
		);

		this.draw();
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		// When destroying a widget not initialized properly, entitytermsforlanguagelistview will
		// not have been created.
		if( this.$entitytermsforlanguagelistview ) {
			var entitytermsforlanguagelistview = this._getEntitytermsforlanguagelistview();

			if( entitytermsforlanguagelistview ) {
				entitytermsforlanguagelistview.destroy();
			}

			this.$entitytermsforlanguagelistview.remove();
		}

		if( this.$entitytermsforlanguagelistviewToggler ) {
			this.$entitytermsforlanguagelistviewToggler.remove();
		}

		if( this.$entitytermsforlanguagelistviewHelp ) {
			this.$entitytermsforlanguagelistviewHelp.remove();
		}

		this.element.off( '.' + this.widgetName );
		this.element.removeClass( 'wikibase-entitytermsview' );
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @inheritdoc
	 */
	draw: function() {
		var self = this,
			deferred = $.Deferred();

		this.$entitytermsforlanguagelistview
			= this.element.find( '.wikibase-entitytermsforlanguagelistview' );

		if( !this.$entitytermsforlanguagelistview.length ) {
			this.$entitytermsforlanguagelistview = $( '<div/>' )
				.appendTo( this.$entitytermsforlanguagelistviewContainer );
		}

		if( !this._getEntitytermsforlanguagelistview() ) {
			this._createEntitytermsforlanguagelistview();
		}

		if(
			!this.element
				.find( '.wikibase-entitytermsview-entitytermsforlanguagelistview-toggler' )
				.length
		) {
			// TODO: Remove as soon as drop-down edit buttons are implemented. The language list may
			// then be shown (without directly switching to edit mode) using the drop down menu.
			this._createEntitytermsforlanguagelistviewToggler();
		}

		if( !this._$notification ) {
			this.notification()
				.appendTo( this._getEntitytermsforlanguagelistview().$header )
				.on( 'closeableupdate.' + this.widgetName, function() {
					var sticknode = self.element.data( 'sticknode' );
					if( sticknode ) {
						sticknode.refresh();
					}
				} );
		}

		return deferred.resolve().promise();
	},

	/**
	 * Creates the dedicated toggler for showing/hiding the list of entity terms. This function is
	 * supposed to be removed as soon as drop-down edit buttons are implemented with the mechanism
	 * toggling the list's visibility while not starting edit mode will be part of the drop-down
	 * menu.
	 * @private
	 */
	_createEntitytermsforlanguagelistviewToggler: function() {
		var self = this,
			api = new mw.Api();

		this.$entitytermsforlanguagelistviewToggler = $( '<div/>' )
			.addClass( 'wikibase-entitytermsview-entitytermsforlanguagelistview-toggler' )
			.text( mw.msg( 'wikibase-entitytermsview-entitytermsforlanguagelistview-toggler' ) )
			.toggler( {
				$subject: this.$entitytermsforlanguagelistviewContainer
			} )
			.on( 'toggleranimation.' + this.widgetName, function( event, params ) {
				if( mw.user.isAnon() ) {
					$.cookie(
						'wikibase-entitytermsview-showEntitytermslistview',
						params.visible,
						{ expires: 365, path: '/' }
					);
				} else {
					api.postWithToken( 'options', {
						action: 'options',
						optionname: 'wikibase-entitytermsview-showEntitytermslistview',
						optionvalue: params.visible ? '1' : '0'
					} )
					.done( function() {
						mw.user.options.set(
							'wikibase-entitytermsview-showEntitytermslistview',
							params.visible ? '1' : '0'
						);
					} );
				}

				// Show "help" link only if the toggler content is visible (decided by Product
				// Management):
				if( self.$entitytermsforlanguagelistviewHelp ) {
					self.$entitytermsforlanguagelistviewHelp[
						params.visible ? 'removeClass' : 'addClass'
					](
						'wikibase-entitytermsview-entitytermsforlanguagelistview-configure-hidden'
					);
				}
			} );

		this.$entitytermsforlanguagelistviewContainer.before(
			this.$entitytermsforlanguagelistviewToggler
		);

		// Inject link to page providing help about how to configure languages:
		// TODO: Remove as soon as soon as some user-friendly mechanism is implemented to define
		// user languages.

		if( mw.config.get( 'wbUserSpecifiedLanguages' ).length > 1 ) {
			// User applied custom configuration, no need to show link to help page.
			return;
		}

		var toggler = this.$entitytermsforlanguagelistviewToggler.data( 'toggler' );

		this.$entitytermsforlanguagelistviewHelp =
			$( '<span/>' )
			.addClass( 'wikibase-entitytermsview-entitytermsforlanguagelistview-configure' )
			.append(
				$( '<a/>' )
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

		if( !toggler.option( '$subject' ).is( ':visible' ) ) {
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
	_getEntitytermsforlanguagelistview: function() {
		return this.$entitytermsforlanguagelistview.data( 'entitytermsforlanguagelistview' );
	},

	/**
	 * Creates and initializes the entitytermsforlanguagelistview widget.
	 */
	_createEntitytermsforlanguagelistview: function() {
		var self = this,
			prefix = $.wikibase.entitytermsforlanguagelistview.prototype.widgetEventPrefix;

		this.$entitytermsforlanguagelistview
		.on( prefix + 'change.' + this.widgetName, function( event ) {
			event.stopPropagation();
			self._trigger( 'change' );
		} )
		.on( prefix + 'toggleerror.' + this.widgetName, function( event, error ) {
			event.stopPropagation();
			self.setError( error );
		} )
		.on(
			[
				prefix + 'create.' + this.widgetName,
				prefix + 'afterstartediting.' + this.widgetName,
				prefix + 'stopediting.' + this.widgetName,
				prefix + 'afterstopediting.' + this.widgetName,
				prefix + 'disable.' + this.widgetName
			].join( ' ' ),
			function( event ) {
				event.stopPropagation();
			}
		)
		.entitytermsforlanguagelistview( {
			value: this.options.value,
			entityId: this.options.entityId,
			entityChangersFactory: this.options.entityChangersFactory
		} );

		this.$entitytermsforlanguagelistview.data( 'entitytermsforlanguagelistview' )
			.$header.sticknode( {
				$container: this.$entitytermsforlanguagelistview
			} );
	},

	/**
	 * @return {boolean}
	 */
	isValid: function() {
		return this._getEntitytermsforlanguagelistview().isValid();
	},

	/**
	 * @return {boolean}
	 */
	isInitialValue: function() {
		return this._getEntitytermsforlanguagelistview().isInitialValue();
	},

	/**
	 * @inheritdoc
	 */
	startEditing: function() {
		this._getEntitytermsforlanguagelistview().startEditing();

		return PARENT.prototype.startEditing.call( this );
	},

	/**
	 * @inheritdoc
	 */
	stopEditing: function( dropValue ) {
		var self = this,
			deferred = $.Deferred();

		if( !this.isInEditMode() || ( !this.isValid() || this.isInitialValue() ) && !dropValue ) {
			return deferred.resolve().promise();
		}

		this._trigger( 'stopediting', null, [dropValue] );

		this.disable();

		this.$entitytermsforlanguagelistview
		.one(
			'entitytermsforlanguagelistviewafterstopediting.entitytermsviewstopediting',
			function( event, dropValue ) {
				self._afterStopEditing( dropValue );
				self.$entitytermsforlanguagelistview.off( '.entitytermsviewstopediting' );
				deferred.resolve();
			}
		)
		.one(
			'entitytermsforlanguagelistviewtoggleerror.entitytermsviewstopediting',
			function( event, error ) {
				self.enable();
				self.$entitytermsforlanguagelistview.off( '.entitytermsviewstopediting' );
				deferred.reject( error );
			}
		);

		this._getEntitytermsforlanguagelistview().stopEditing( dropValue );

		return deferred.promise();
	},

	/**
	 * @inheritdoc
	 */
	_save: function() {
		// Currently unused.
		// TODO: Implement function directly saving all (updated) entity terms instead of deferring
		// the functionality to sub-components.
	},

	/**
	 * @param {boolean} dropValue
	 */
	_afterStopEditing: function( dropValue ) {
		this.notification();
		if( !dropValue ) {
			this.options.value = this.value();
		}
		return PARENT.prototype._afterStopEditing.apply( this, arguments );
	},

	/**
	 * @inheritdoc
	 */
	focus: function() {
		this._getEntitytermsforlanguagelistview().focus();
	},

	/**
	 * @inheritdoc
	 */
	removeError: function() {
		this.element.removeClass( 'wb-error' );
		this._getEntitytermsforlanguagelistview().removeError();
	},

	/**
	 * @inheritdoc
	 *
	 * @param {Object[]} [value]
	 * @return {Object[]|*}
	 */
	value: function( value ) {
		if( value !== undefined ) {
			return this.option( 'value', value );
		}

		return this._getEntitytermsforlanguagelistview().value();
	},

	/**
	 * @inheritdoc
	 */
	isEmpty: function() {
		return this._getEntitytermsforlanguagelistview().isEmpty();
	},

	/**
	 * @inheritdoc
	 */
	_setOption: function( key, value ) {
		if( key === 'value' ) {
			throw new Error( 'Impossible to set value after initialization' );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this._getEntitytermsforlanguagelistview().option( key, value );
		}

		return response;
	},

	/**
	 * @inheritdoc
	 */
	notification: function( $content, additionalCssClasses ) {
		if( !this._$notification ) {
			var $closeable = $( '<div/>' ).closeable();

			this._$notification = $( '<tr/>' ).append( $( '<td/>' ).append( $closeable ) );

			this._$notification.data( 'closeable', $closeable.data( 'closeable' ) );
		}

		var $headerTr = this._getEntitytermsforlanguagelistview().$header.children( 'tr' ).first();
		this._$notification.children( 'td' ).attr( 'colspan', $headerTr.children().length );

		this._$notification.data( 'closeable' ).setContent( $content, additionalCssClasses );
		return this._$notification;
	}
} );

}( mediaWiki, jQuery ) );
