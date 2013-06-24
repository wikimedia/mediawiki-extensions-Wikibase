/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 *
 * @author H. Snater < mediawiki@snater.com >
 */
// TODO: Remove mediaWiki dependency
( function( dv, vp, $, vv, globeCoordinate, mw ) {
	'use strict';

	var GlobeCoordinate = globeCoordinate.GlobeCoordinate,
		globeCoordinateSettings = globeCoordinate.settings;

	var PARENT = vv.Expert;

	/**
	 * Valueview expert handling input of globe coordinate values.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.Expert
	 */
	vv.experts.GlobeCoordinateInput = vv.expert( 'globecoordinateinput', PARENT, {
		/**
		 * The the input element's node.
		 * @type {jQuery}
		 */
		$input: null,

		/**
		 * Caches a new value (or null for no value) set by _setRawValue() until draw() displaying
		 * the new value has been called. The use of this, basically, is a structural improvement
		 * which allows moving setting the displayed value to the draw() method which is supposed to
		 * handle all visual manners.
		 * @type {globeCoordinate.GlobeCoordinate|null|false}
		 */
		_newValue: null,

		/**
		 * Current value. Needs to be cached because the value cannot simply be parsed from the
		 * current input element value since that would lead to the precision being set in the
		 * sexagesimal system even when not intended.
		 * @type {globeCoordinate.GlobeCoordinate|null}
		 */
		_value: null,

		/**
		 * The preview widget.
		 * @type {jQuery.valueview.preview}
		 */
		preview: null,

		/**
		 * Container node for precision input and label.
		 * @type {jQuery}
		 */
		$precisionContainer: null,

		/**
		 * Node of the widget used to specify the precision.
		 * @type {jQuery}
		 */
		$precision: null,

		/**
		 * @see jQuery.valueview.Expert._init
		 */
		_init: function() {
			var self = this,
				listrotatorEvents = 'listrotatorauto.' + this.uiBaseClass
					+ ' listrotatorselected.' + this.uiBaseClass,
				precisionValues = [];

			this.$precisionContainer = $( '<div/>' )
			.addClass( this.uiBaseClass + '-precisioncontainer' )
			.append(
				$( '<div/>' ).text( mw.msg( 'valueview-expert-globecoordinateinput-precision' ) )
			);

			$.each( globeCoordinateSettings.precisions, function( i, precisionDefinition ) {
				var label = globeCoordinate.precisionText( precisionDefinition.level );
				precisionValues.push( {
					value: roundPrecision( precisionDefinition.level ),
					label: label
				} );
			} );

			this.$precision = $( '<div/>' )
				.addClass( this.uiBaseClass + '-precision' )
				.listrotator( {
					values: precisionValues.reverse(),
					deferInit: true
				} )
				.on( listrotatorEvents, function( event, newValue ) {
					var rawValue = self._getRawValue(),
						roundedPrecision = roundPrecision( rawValue.getPrecision() ) ;

					if( rawValue === null || newValue === roundedPrecision ) {
						// Listrotator has been rotated automatically, the value covering the new
						// precision has already been generated or the current input is invalid.
						return;
					}

					self._updateValue().done( function( gc ) {
						if( event.type === 'listrotatorauto' ) {
							self.$precision.data( 'listrotator' ).rotate( gc.getValue().getPrecision() );
						}
					} );
				} )
				.appendTo( this.$precisionContainer );

			var $toggler = $( '<a/>' )
			.addClass( this.uiBaseClass + '-advancedtoggler' )
			.text( mw.msg( 'valueview-expert-advancedadjustments' ) );

			this.$input = $( '<input/>', {
				type: 'text',
				'class': this.uiBaseClass + '-input valueview-input'
			} )
			.appendTo( this.$viewPort );

			var $preview = $( '<div/>' ).preview( { $input: this.$input } );
			this.preview = $preview.data( 'preview' );

			this.$input.eachchange( function( event, oldValue ) {
				var currentInputValue = self.$input.val();

				self._setRawValue( currentInputValue );

				// No need to update the preview when the input element is cleared since it will
				// be hidden anyway.
				if( self.$input.val() === '' ) {
					self._updatePreview( null );
					return;
				}

				self.preview.showSpinner();

				self.parser().parse( self.$input.val() )
				.done( function( dataValue ) {
					// Throw away outdated requests:
					if( currentInputValue !== self.$input.val() ) {
						return;
					}

					self._setRawValue( dataValue.getValue() );

					self.$precision.data( 'listrotator' ).rotate(
						roundPrecision( dataValue.getValue().getPrecision() )
					);

					self._newValue = false; // value, not yet handled by draw(), is outdated now
					self._updatePreview( dataValue.getValue() );
					self._viewNotifier.notify( 'change' );
				} )
				.fail( function() {
					if( currentInputValue !== self.$input.val() ) {
						return;
					}
					self._updatePreview( null );
				} );
			} )
			.inputextender( {
				content: [ $preview, $toggler, this.$precisionContainer ],
				initCallback: function() {
					self.$precision.data( 'listrotator' ).initWidths();
					self.$precisionContainer.css( 'display', 'none' );
					$toggler.toggler( { $subject: self.$precisionContainer } );
				}
			} );

		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			this.$precision.data( 'listrotator' ).destroy();
			this.$precision.remove();
			this.$precisionContainer.remove();

			var previewElement = this.preview.element;
			this.preview.destroy();
			previewElement.remove();

			this.$input.data( 'inputextender' ).destroy();
			this.$input.remove();

			PARENT.prototype.destroy.call( this );
		},

		/**
		 * Builds a GlobeCoordinate object from the widget's current input taking the precision into
		 * account if set manually.
		 * @since 0.1
		 *
		 * @return {jQuery.Promise}
		 */
		_updateValue: function() {
			var currentInputValue = this.$input.val();

			this.preview.showSpinner();

			var self = this;
			return this.parser().parse( this.$input.val() )
			.done( function( gc ) {
				// Throw away outdated requests:
				if( currentInputValue !== self.$input.val() ) {
					return;
				}
				self._setRawValue( gc.getValue() );
				self._updatePreview( gc.getValue() );
				self._viewNotifier.notify( 'change' );
			} )
			.fail( function() {
				if( currentInputValue !== self.$input.val() ) {
					return;
				}
				self._setRawValue( null );
				self._updatePreview( null );
				self._viewNotifier.notify( 'change' );
			} );
		},

		/**
		 * Updates the preview.
		 * @since 0.1
		 *
		 * @param {globeCoordinate.GlobeCoordinate|null} gc
		 */
		_updatePreview: function( gc ) {
			if( gc !== null ) {
				gc = gc.degreeText();
			}
			this.preview.update( gc );
		},

		/**
		 * @see jQuery.valueview.Expert.parser
		 */
		parser: function() {
			var precisionWidget = this.$precision.data( 'listrotator' ),
				options = ( !precisionWidget.$auto.hasClass( 'ui-state-active' ) )
					? { precision: getPrecisionSetting( precisionWidget.value() ) }
					: {};

			return new vp.GlobeCoordinateParser( options );
		},

		/**
		 * @see jQuery.valueview.Expert._getRawValue
		 *
		 * @return {globeCoordinate.GlobeCoordinate|string|null}
		 */
		_getRawValue: function() {
			return ( this._newValue !== false ) ? this._newValue : this._value;
		},

		/**
		 * @see jQuery.valueview.Expert._setRawValue
		 *
		 * @param {globeCoordinate.GlobeCoordinate|string|null} globeCoordinate
		 */
		_setRawValue: function( globeCoordinate ) {
			this._newValue = this._value = globeCoordinate;
		},

		/**
		 * @see jQuery.valueview.Expert.rawValueCompare
		 */
		rawValueCompare: function( globeCoordinate1, globeCoordinate2 ) {
			if( globeCoordinate2 === undefined ) {
				globeCoordinate2 = this._getRawValue();
			}

			if( globeCoordinate1 === null && globeCoordinate2 === null ) {
				return true;
			}

			if( globeCoordinate1 instanceof GlobeCoordinate ) {
				globeCoordinate1 = globeCoordinate1.degreeText();
			}

			if( globeCoordinate2 instanceof GlobeCoordinate ) {
				globeCoordinate2 = globeCoordinate2.degreeText();
			}

			return globeCoordinate1 === globeCoordinate2;
		},

		/**
		 * @see jQuery.valueview.Expert.draw
		 *
		 * @return {jQuery.Promise|null}
		 *
		 * TODO: Get rid of this LSP violation, should not be necessary to return a promise here.
		 */
		draw: function() {
			var self = this,
				promise = null;

			if( this._viewState.isDisabled() ) {
				var deferred = $.Deferred().reject();
				promise = deferred.promise();
				this.$input.prop( 'disabled', true ).addClass( 'ui-state-disabled' );
			} else {
				this.$input.prop( 'disabled', false ).removeClass( 'ui-state-disabled' );

				if( this._newValue !== false ) {
					if( this._newValue !== null ) {
						promise = this.parser().parse( this._newValue )
						.done( function( dataValue ) {
							var gc = dataValue.getValue();
							self.$input.val( gc.degreeText() );
							self.$precision.data( 'listrotator' ).value(
								roundPrecision( gc.getPrecision() )
							);
							self._updatePreview( gc );
						} ).fail( function() {
							self._updatePreview( null );
						} );
					}
					this._newValue = false;
				}
			}

			return promise;
		},

		/**
		 * @see jQuery.valueview.Expert.focus
		 */
		focus: function() {
			this.$input.focusAt( 'end' );
		},

		/**
		 * @see jQuery.valueview.Expert.blur
		 */
		blur: function() {
			this.$input.blur();
		}
	} );

	/**
	 * Rounds a given precision for being able to use is as internal "constant".
	 *
	 * @since 0.1
	 *
	 * @param {number} precision
	 * @return {number}
	 */
	function roundPrecision( precision ) {
		var precisions = globeCoordinateSettings.precisions,
			highestPrecision = precisions[precisions.length - 1].level,
			multiplier = 1;

		// To make sure that not too much digits are cut off for the "constant" precisions to be
		// distinctive, we round with a multiplier that rounds the most precise precision to a
		// number greater than 1:
		while( highestPrecision * multiplier < 1 ) {
			multiplier *= 10;
		}

		return Math.round( precision * multiplier ) / multiplier;
	}

	/**
	 * Returns the original precision level for a rounded precision.
	 *
	 * @since 0.1
	 *
	 * @param {number} roundedPrecision
	 * @return {number}
	 */
	function getPrecisionSetting( roundedPrecision ) {
		var rounded,
			precision;

		$.each( globeCoordinateSettings.precisions, function( i, precisionDefinition ) {
			rounded = roundPrecision( precisionDefinition.level );
			if( rounded === roundedPrecision ) {
				precision = precisionDefinition.level;
				return false;
			}
		} );

		return precision;
	}

}( dataValues, valueParsers, jQuery, jQuery.valueview, globeCoordinate, mediaWiki ) );
