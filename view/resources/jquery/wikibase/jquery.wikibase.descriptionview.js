/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget,
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * Manages a description.
	 *
	 * @extends jQuery.ui.EditableTemplatedWidget
	 *
	 * @option {datamodel.Term} value
	 *
	 * @option {string} [inputNodeName='TEXTAREA']
	 *         Should either be 'TEXTAREA' or 'INPUT'.
	 * @option {boolean} [readOnly=false]
	 *         Whether the input should be read only.
	 * @option {string} [placeholderMessage='wikibase-description-edit-placeholder-language-aware']
	 * @option {string|null} [accessibilityLabel]
	 *         Will be added to the input/textarea as aria-label.
	 */
	$.widget( 'wikibase.descriptionview', PARENT, {
		/**
		 * @see jQuery.ui.TemplatedWidget.options
		 */
		options: {
			template: 'wikibase-descriptionview',
			templateParams: [
				'', // additional class
				'', // text
				'' // toolbar
			],
			templateShortCuts: {
				$text: '.wikibase-descriptionview-text'
			},
			value: null,
			inputNodeName: 'TEXTAREA',
			readOnly: false,
			placeholderMessage: 'wikibase-description-edit-placeholder-language-aware',
			accessibilityLabel: null
		},

		/**
		 * @see jQuery.ui.TemplatedWidget._create
		 *
		 * @throws {Error} if required parameters are not specified properly.
		 */
		_create: function () {
			if ( !( this.options.value instanceof datamodel.Term )
				|| this.options.inputNodeName !== 'INPUT' && this.options.inputNodeName !== 'TEXTAREA'
			) {
				throw new Error( 'Required parameter(s) missing' );
			}

			var self = this;

			this.element
				.on(
					'descriptionviewafterstartediting.' + this.widgetName
					+ ' eachchange.' + this.widgetName,
					( event ) => {
						if ( self.value().getText() === '' ) {
							// Since the widget shall not be in view mode when there is no value, triggering
							// the event without a proper value is only done when creating the widget. Disabling
							// other edit buttons shall be avoided.
							// TODO: Move logic to a sensible place.
							self.element.addClass( 'wb-empty' );
							return;
						}

						self.element.removeClass( 'wb-empty' );
					}
				);

			PARENT.prototype._create.call( this );

			if ( this.$text.text() === '' ) {
				this.draw();
			}
		},

		/**
		 * @see jQuery.ui.TemplatedWidget.destroy
		 */
		destroy: function () {
			if ( this.isInEditMode() ) {
				var self = this;

				this.element.one( this.widgetEventPrefix + 'afterstopediting', ( event ) => {
					PARENT.prototype.destroy.call( self );
				} );

				this.stopEditing( true );
			} else {
				PARENT.prototype.destroy.call( this );
			}
		},

		/**
		 * Main draw routine.
		 */
		draw: function () {
			var done = $.Deferred().resolve().promise();
			var self = this,
				languageCode = this.options.value.getLanguageCode(),
				descriptionText = this.options.value.getText();

			if ( descriptionText === '' ) {
				descriptionText = null;
			}

			this.element[ descriptionText ? 'removeClass' : 'addClass' ]( 'wb-empty' );

			if ( !this.isInEditMode() && !descriptionText ) {
				if ( languageCode === 'mul' ) {
					this.$text.empty().append(
						this._createDescriptionNotApplicableElements()
					);
				} else {
					this.$text.text( mw.msg( 'wikibase-description-empty' ) );
				}
				// Apply lang and dir of UI language
				// instead language of that row
				var userLanguage = mw.config.get( 'wgUserLanguage' );
				this.element
				.attr( 'lang', userLanguage )
				.attr( 'dir', $.util.getDirectionality( userLanguage ) );
				return done;
			}

			this.element
			.attr( 'lang', languageCode )
			.attr( 'dir', $.util.getDirectionality( languageCode ) );

			if ( !this.isInEditMode() ) {
				this.$text.text( descriptionText );
				return done;
			}

			var $input = $( document.createElement( this.options.inputNodeName ) );

			$input
			.addClass( this.widgetFullName + '-input' )
			.attr( 'placeholder', mw.msg(
				// The following messages can be used here:
				// * wikibase-description-edit-placeholder-language-aware
				// * wikibase-description-edit-placeholder-not-applicable
				this.options.placeholderMessage,
				wb.getLanguageNameByCodeForTerms( languageCode )
			) )
			.attr( 'lang', languageCode )
			.attr( 'dir', $.util.getDirectionality( languageCode ) )
			.on( 'keydown.' + this.widgetName, ( event ) => {
				if ( event.keyCode === $.ui.keyCode.ENTER ) {
					event.preventDefault();
				}
			} )
			.on( 'eachchange.' + this.widgetName, ( event ) => {
				self._trigger( 'change' );
			} );

			if ( descriptionText ) {
				$input.val( descriptionText );
			}

			if ( $.fn.inputautoexpand ) {
				$input.inputautoexpand( {
					expandHeight: true,
					suppressNewLine: true
				} );
			}

			if ( this.options.readOnly ) {
				$input.prop( 'readOnly', true );
			}

			if ( this.options.accessibilityLabel ) {
				$input.attr( 'aria-label', this.options.accessibilityLabel );
			}

			this.$text.empty().append( $input );
			return done;
		},

		_startEditing: function () {
			// FIXME: This could be much faster
			return this.draw();
		},

		_stopEditing: function () {
			// FIXME: This could be much faster
			return this.draw();
		},

		/**
		 * @see jQuery.ui.TemplatedWidget._setOption
		 */
		_setOption: function ( key, value ) {
			if ( key === 'value' && !( value instanceof datamodel.Term ) ) {
				throw new Error( 'Value needs to be a datamodel.Term instance' );
			}

			var response = PARENT.prototype._setOption.call( this, key, value );

			if ( key === 'disabled' && this.isInEditMode() ) {
				this.$text.children( '.' + this.widgetFullName + '-input' ).prop( 'disabled', value );
			}

			return response;
		},

		_createDescriptionNotApplicableElements: function () {
			var $abbr = $( '<abbr>' ).attr( 'title', mw.msg( 'wikibase-description-not-applicable-title' ) );
			var $abbrText = $( '<span>' )
				.text( mw.msg( 'wikibase-description-not-applicable' ) )
				.attr( 'aria-hidden', 'true' );
			$abbr.append( $abbrText );
			return $abbr;
		},

		/**
		 * Gets/Sets the widget's value.
		 *
		 * @param {datamodel.Term} [value]
		 * @return {datamodel.Term|undefined}
		 */
		value: function ( value ) {
			if ( value !== undefined ) {
				return this.option( 'value', value );
			}

			if ( !this.isInEditMode() ) {
				return this.options.value;
			}

			return new datamodel.Term(
				this.options.value.getLanguageCode(),
				this.$text.children( '.' + this.widgetFullName + '-input' ).val().trim()
			);
		},

		/**
		 * @see jQuery.ui.TemplatedWidget.focus
		 */
		focus: function () {
			if ( this.isInEditMode() ) {
				this.$text.children( '.' + this.widgetFullName + '-input' ).trigger( 'focus' );
			} else {
				this.element.trigger( 'focus' );
			}
		}

	} );

}( wikibase ) );
