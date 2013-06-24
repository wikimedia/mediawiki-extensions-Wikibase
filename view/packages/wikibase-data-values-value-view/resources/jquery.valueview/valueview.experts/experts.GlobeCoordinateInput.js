/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 *
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
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
		 * @type {string|null|false}
		 */
		_newValue: null,

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
				notifier = this._viewNotifier,
				precisionValues = [],
				listrotatorEvents = 'listrotatorauto listrotatorselected'
					.replace( /(\w+)/g, '$1.' + this.uiBaseClass );

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
				.on( listrotatorEvents, function( event, newPrecisionLevel ) {
					var currentValue = self.viewState().value();

					if( currentValue === null ) {
						// current rawValue must be invalid anyhow
						return;
					}

					var currentPrecision = roundPrecision(
							currentValue.getValue().getPrecision() );

					if( newPrecisionLevel === currentPrecision ) {
						// Listrotator has been rotated automatically or the value covering the new
						// precision has already been generated.
						return;
					}

					notifier.notify( 'change' );
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
				notifier.notify( 'change' );
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
		 * @see jQuery.valueview.Expert.parser
		 */
		parser: function() {
			return new vp.GlobeCoordinateParser();
		},

		/**
		 * @see jQuery.valueview.Expert.valueCharacteristics
		 */
		valueCharacteristics: function() {
			if( !this.$precision ) { // happens when used by BifidExpert ...
				return {};
			}

			var options = {},
				precisionWidget = this.$precision.data( 'listrotator' );

			if( !precisionWidget.autoActive() ) {
				var precision = getPrecisionSetting( precisionWidget.value() );

				if( precision !== null ) {
					options.precision = precision;
				}
			}

			return options;
		},

		/**
		 * @see jQuery.valueview.Expert._getRawValue
		 *
		 * @return {string|null}
		 */
		_getRawValue: function() {
			var value = this._newValue !== false ? this._newValue : this.$input.val();

			if( $.trim( value ) === '' ) {
				return null;
			}
			return value;
		},

		/**
		 * @see jQuery.valueview.Expert._setRawValue
		 */
		_setRawValue: function( rawValue ) {
			if( rawValue instanceof GlobeCoordinate ) {
				rawValue = rawValue.degreeText();
			}
			else if( typeof rawValue !== 'string' ) {
				rawValue = null;
			}
			this._newValue = rawValue;
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
		 */
		draw: function() {
			var geoValue = this.viewState().value(),
				$input = this.$input;

			if( this._viewState.isDisabled() ) {
				$input.prop( 'disabled', true ).addClass( 'ui-state-disabled' );
			} else {
				$input.prop( 'disabled', false ).removeClass( 'ui-state-disabled' );
			}

			if( this._newValue !== false ) {
				var newText = this._newValue || '';

				if( $input.val() !== newText ) {
					$input.val( newText );
				}
			}

			if( this._newValue
				|| this.$precision.data( 'listrotator' ).autoActive()
			) {
				// hacky update of precision, just assume the raw value is the value we have in
				// the valueview right now.
				if( geoValue ) {
					this.$precision.data( 'listrotator' ).value(
						roundPrecision( geoValue.getValue().getPrecision() ) );
				}
			}

			this._newValue = false;

			// Update preview:
			this.preview.update( geoValue && geoValue.getValue().degreeText() );
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
	 * Rounds a given precision for being able to use it as internal "constant".
	 *
	 * TODO: This should not even be necessary. Make sure GlobeCoordinateValue objects can be
	 *  used without having to take care of their precision first if they are generated by the
	 *  backend parser.
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
	 * @return {number|null}
	 */
	function getPrecisionSetting( roundedPrecision ) {
		var rounded,
			precision = null;

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
