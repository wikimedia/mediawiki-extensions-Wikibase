/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, mw, wb ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * Manages a aliases.
 * @since 0.5
 * @extends jQuery.ui.TemplatedWidget
 *
 * @option {Object|null} value
 *         Object representing the widget's value.
 *         Structure: { language: <{string}>, aliases: <{string[]}> }
 *
 * @option {string} [helpMessage]
 *         Default: mw.msg( 'wikibase-aliases-input-help-message' )
 *
 * @options {string} entityId
 *
 * @option {wikibase.RepoApi} api
 *
 * @option {wikibase.store.EntityStore} entityStore
 */
$.widget( 'wikibase.aliasesview', PARENT, {
	/**
	 * @see jQuery.ui.TemplatedWidget.options
	 */
	options: {
		template: 'wikibase-aliasesview',
		templateParams: [
			'', // additional class
			mw.msg( 'wikibase-aliases-label' ), // label
			'', // list items
			'' // toolbar
		],
		templateShortCuts: {
			'$label': '.wikibase-aliasesview-label',
			'$list': 'ul'
		},
		value: null,
		helpMessage: mw.msg( 'wikibase-aliases-input-help-message' ),
		entityId: null,
		api: null
	},

	/**
	 * @type {boolean}
	 */
	_isInEditMode: false,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 *
	 * @throws {Error} if required parameters are not specified properly.
	 */
	_create: function() {
		if( !this.options.entityId || !this.options.api ) {
			throw new Error( 'Required option(s) missing' );
		}

		this.options.value = this._checkValue( this.options.value );

		PARENT.prototype._create.call( this );

		this.element.removeClass( 'wb-empty' );
		this.$label.text( mw.msg( 'wikibase-aliases-label' ) );

		var value = this.options.value;

		if(
			value && value.aliases.length
			&& this.$list.children( 'li' ).length !== value.aliases.length
		) {
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
	 * Main draw routine.
	 */
	_draw: function() {
		this.$list.off( '.' + this.widgetName );

		if( !this._isInEditMode ) {
			var tagadata = this.$list.data( 'tagadata' );

			if( tagadata ) {
				tagadata.destroy();
			}

			this.element.removeClass( 'wb-edit' );

			this.$list.empty();
			if( !this.options.value ) {
				return;
			}

			for( var i = 0; i < this.options.value.aliases.length; i++ ) {
				this.$list.append(
					mw.template( 'wikibase-aliasesview-list-item', this.options.value.aliases[i] )
				);
			}

			return;
		}

		this.element.addClass( 'wb-edit' );

		this._initTagadata();
	},

	/**
	 * Creates and initializes the tagadata widget.
	 */
	_initTagadata: function() {
		var self = this;

		this.$list
		.tagadata( {
			animate: false,
			placeholderText: mw.msg( 'wikibase-alias-edit-placeholder' )
		} )
		.on(
			'tagadatatagremoved.' + this.widgetName
			+ ' tagadatatagchanged.' + this.widgetName
			+ ' tagadatatagremoved.' + this.widgetName, function( event ) {
				self._trigger( 'change' );
			}
		);

		var expansionOptions = {
			expandOnResize: false,
			comfortZone: 16, // width of .ui-icon
			maxWidth: function() {
				// TODO/FIXME: figure out why this requires at least -17, can't be because of padding + border
				// which is only 6 for both sides
				return self.$list.width() - 20;
			}
			/*
			// TODO/FIXME: both solutions are not perfect, when tag larger than available space either the
			// input will be auto-resized and not show the whole text or we still show the whole tag but it
			// will break the site layout. A solution would be replacing input with textarea.
			maxWidth: function() {
				var tagList = self._getTagadata().tagList;
				var origCssDisplay = tagList.css( 'display' );
				tagList.css( 'display', 'block' );
				var width = tagList.width();
				tagList.css( 'display', origCssDisplay );
				return width;
			}
			 */
		};

		var tagadata = this.$list.data( 'tagadata' );

		// calculate size for all input elements initially:
		tagadata.getTags().add( tagadata.getHelperTag() )
			.find( 'input' ).inputautoexpand( expansionOptions );

		// also make sure that new helper tags will calculate size correctly:
		this.$list.on( 'tagadatahelpertagadded.' + this.widgetName, function( event, tag ) {
			$( tag ).find( 'input' ).inputautoexpand( expansionOptions );
		} );
	},

	/**
	 * Starts the widget's edit mode.
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

		if( dropValue ) {
			this._afterStopEditing( dropValue );
			return;
		}

		this.disable();

		this._trigger( 'stopediting', null, [dropValue] );

		// TODO: Performing API interaction should be managed in parent component (probably
		// entityview)
		this._save()
		.done( function() {
			self.enable();
			self._afterStopEditing( dropValue );
		} )
		.fail( function( errorCode, details ) {
			// TODO: API should return an Error object
			var error = wb.RepoApiError.newFromApiResponse( errorCode, details, 'save' );
			self.setError( error );
		} );
	},

	/**
	 * @return {jQuery.Promise}
	 */
	_save: function() {
		return this.options.api.setAliases(
			this.options.entityId,
			wb.getRevisionStore().getAliasesRevision(),
			this._getNewAliases(),
			this._getRemovedAliases(),
			this.options.value.language
		)
		.done( function( response ) {
			wb.getRevisionStore().setAliasesRevision( response.entity.lastrevid );
		} );
	},

	/**
	 * Cancels the widget's edit mode.
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
	 * @return {string[]}
	 */
	_getNewAliases: function() {
		var currentAliases = this.value().aliases,
			newAliases = [];

		for( var i = 0; i < currentAliases.length; i++ ) {
			if( $.inArray( currentAliases[i], this.options.value.aliases ) === -1 ) {
				newAliases.push( currentAliases[i] );
			}
		}

		return newAliases;
	},

	/**
	 * @return {string[]}
	 */
	_getRemovedAliases: function() {
		var currentAliases = this.value().aliases,
			initialAliases = this.options.value.aliases,
			removedAliases = [];

		for( var i = 0; i < initialAliases.length; i++ ) {
			if( $.inArray( initialAliases[i], currentAliases ) === -1 ) {
				removedAliases.push( initialAliases[i] );
			}
		}

		return removedAliases;
	},

	/**
	 * @return {boolean}
	 */
	isValid: function() {
		// Function required by edittoolbar.
		return true;
	},

	/**
	 * @return {boolean}
	 */
	isInitialValue: function() {
		var initialValue = this.options.value,
			currentValue = this.value();

		if(
			currentValue.language !== initialValue.language
			|| currentValue.aliases.length !== initialValue.aliases.length
		) {
			return false;
		}

		for( var i = 0; i < currentValue.aliases.length; i++ ) {
			if( currentValue.aliases[i] !== initialValue.aliases[i] ) {
				return false;
			}
		}

		return true;
	},

	/**
	 * Toggles error state.
	 *
	 * @param {Error} error
	 */
	setError: function( error ) {
		if( error ) {
			this.element.addClass( 'wb-error' );
			this._trigger( 'toggleerror', null, [error] );
		} else {
			this.element.removeClass( 'wb-error' );
			this._trigger( 'toggleerror' );
		}
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		if( key === 'value' ) {
			value = this._checkValue( value );
		}
		return PARENT.prototype._setOption.call( this, key, value );
	},

	/**
	 * @param {*} value
	 * @return {Object}
	 *
	 * @throws {Error} if value is not defined properly.
	 */
	_checkValue: function( value ) {
		if( !$.isPlainObject( value ) ) {
			throw new Error( 'Value needs to be an object' );
		} else if( !value.language ) {
			throw new Error( 'Value needs language to be specified' );
		}

		if( !value.aliases ) {
			value.aliases = [];
		}

		return value;
	},

	/**
	 * Gets/Sets the widget's value.
	 *
	 * @param {Object} [value]
	 * @return {Object|undefined}
	 */
	value: function( value ) {
		if( value !== undefined ) {
			this.option( 'value', value );
			return;
		}

		if( !this._isInEditMode ) {
			return this.option( 'value' );
		}

		var tagadata = this.$list.data( 'tagadata' );

		value = $.map( tagadata.getTags(), function( tag ) {
			return tagadata.getTagLabel( $( tag ) );
		} );

		return {
			language: this.options.value.language,
			aliases: value
		};
	},

	/**
	 * Puts Keyboard focus on the widget.
	 */
	focus: function() {
		if( this._isInEditMode ) {
			this.$list.data( 'tagadata' ).getHelperTag().find( 'input' ).focus();
		}
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.disable
	 */
	disable: function() {
		if( this._isInEditMode ) {
			this.$list.data( 'tagadata' ).disable();
		}

		return PARENT.prototype.disable.call( this );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.enable
	 */
	enable: function() {
		if( this._isInEditMode ) {
			this.$list.data( 'tagadata' ).enable();
		}

		return PARENT.prototype.enable.call( this );
	}

} );

$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'aliasesview',
	events: {
		aliasesviewcreate: function( event, toolbarcontroller ) {
			var $aliasesview = $( event.target ),
				aliasesview = $aliasesview.data( 'aliasesview' );

			$aliasesview.edittoolbar( {
				$container: $( '<div/>' ).insertAfter( $aliasesview.find( 'ul' ) ),
				interactionWidget: aliasesview
			} );

			$aliasesview.on( 'keyup', function( event ) {
				if( aliasesview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					aliasesview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					aliasesview.stopEditing( false );
				}
			} );

			$aliasesview.one( 'edittoolbaredit', function() {
				toolbarcontroller.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					aliasesview.widgetEventPrefix + 'change',
					function( event ) {
						var $aliasesview = $( event.target ),
							aliasesview = $aliasesview.data( 'aliasesview' ),
							edittoolbar = $aliasesview.data( 'edittoolbar' ),
							btnSave = edittoolbar.getButton( 'save' ),
							enable = aliasesview.isValid() && !aliasesview.isInitialValue();

						btnSave[enable ? 'enable' : 'disable']();
					}
				);
			} );
		},
		aliasesviewdisable: function( event ) {
			var $aliasesview = $( event.target ),
				aliasesview = $aliasesview.data( 'aliasesview' ),
				edittoolbar = $aliasesview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = aliasesview.isValid() && !aliasesview.isInitialValue(),
				currentAliases = aliasesview.value().aliases;

			btnSave[enable ? 'enable' : 'disable']();

			if( aliasesview.option( 'disabled' ) || currentAliases.length ) {
				return;
			}

			if( !currentAliases ) {
				edittoolbar.disable();
			}
		},
		edittoolbaredit: function( event, toolbarcontroller ) {
			var $aliasesview = $( event.target ),
				aliasesview = $aliasesview.data( 'aliasesview' );

			if( !aliasesview ) {
				return;
			}

			aliasesview.focus();
		}
	}
} );

}( jQuery, mediaWiki, wikibase ) );
