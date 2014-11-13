/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, mw, wb ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

/**
 * Manages aliases.
 * @since 0.5
 * @extends jQuery.ui.EditableTemplatedWidget
 *
 * @option {wikibase.datamodel.MultiTerm} value
 *
 * @option {string} [helpMessage]
 *         Default: mw.msg( 'wikibase-aliases-input-help-message' )
 *
 * @option {wikibase.entityChangers.AliasesChanger} aliasesChanger
 */
$.widget( 'wikibase.aliasesview', PARENT, {
	/**
	 * @see jQuery.ui.EditableTemplatedWidget.options
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
		aliasesChanger: null
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		if(
			!( this.options.value instanceof wb.datamodel.MultiTerm )
			|| !this.options.aliasesChanger
		) {
			throw new Error( 'Required option(s) missing' );
		}

		PARENT.prototype._create.call( this );

		this.element.removeClass( 'wb-empty' );
		this.$label.text( mw.msg( 'wikibase-aliases-label' ) );

		if( this.$list.children( 'li' ).length !== this.options.value.getTexts().length ) {
			this.draw();
		}
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.draw
	 */
	draw: function() {
		this.$list.off( '.' + this.widgetName );

		if( this.isInEditMode() ) {
			this._initTagadata();
		} else {
			var self = this,
				tagadata = this.$list.data( 'tagadata' );

			if( tagadata ) {
				tagadata.destroy();
			}

			this.$list.empty();

			$.each( this.options.value.getTexts(), function() {
				self.$list.append( mw.wbTemplate( 'wikibase-aliasesview-list-item', this ) );
			} );
		}

		return $.Deferred().resolve().promise();
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
	 * @see jQuery.ui.EditableTemplatedWidget.save
	 */
	_save: function() {
		return this.options.aliasesChanger.setAliases( this.value() );
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.isValid
	 */
	isValid: function() {
		return true;
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.isValid
	 */
	isInitialValue: function() {
		return this.value().equals( this.options.value );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		if( key === 'value' && !( value instanceof wb.datamodel.MultiTerm ) ) {
			throw new Error( 'Value needs to be a wb.datamodel.MultiTerm instance' );
		}

		var response = PARENT.prototype._setOption.call( this, key, value );

		if( key === 'disabled' && this.isInEditMode() ) {
			this.$list.data( 'tagadata' ).option( 'disabled', value );
		}

		return response;
	},

	/**
	 * Gets/Sets the widget's value.
	 *
	 * @param {wikibase.datamodel.MultiTerm} [value]
	 * @return {wikibase.datamodel.MultiTerm|undefined}
	 */
	value: function( value ) {
		if( value !== undefined ) {
			this.option( 'value', value );
			return;
		}

		if( !this.isInEditMode() ) {
			return this.option( 'value' );
		}

		var tagadata = this.$list.data( 'tagadata' );

		return new wb.datamodel.MultiTerm(
			this.options.value.getLanguageCode(),
			$.map( tagadata.getTags(), function( tag ) {
				return tagadata.getTagLabel( $( tag ) );
			} )
		);
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.focus
	 */
	focus: function() {
		if( this.isInEditMode() ) {
			this.$list.data( 'tagadata' ).getHelperTag().find( 'input' ).focus();
		} else {
			this.element.focus();
		}
	}

} );

$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'aliasesview',
	events: {
		aliasesviewcreate: function( event, toolbarcontroller ) {
			var $aliasesview = $( event.target ),
				aliasesview = $aliasesview.data( 'aliasesview' ),
				$container = $aliasesview.find( 'ul' ).next( 'span' );

			if( !$container.length ) {
				$container = $( '<span/>' ).insertAfter( $aliasesview.find( 'ul' ) );
			}

			$aliasesview.edittoolbar( {
				$container: $container,
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
				currentAliases = aliasesview.value();

			btnSave[enable ? 'enable' : 'disable']();

			if( aliasesview.option( 'disabled' ) || currentAliases && currentAliases.length ) {
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
