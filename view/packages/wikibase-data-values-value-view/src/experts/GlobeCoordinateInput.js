/**
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, vv, GlobeCoordinate, Formatter ) {
	'use strict';

	/**
	 * Globe coordinate formatter
	 *
	 * We need this for rawValueCompare because it is synchronous.
	 *
	 * @todo Replace this with a call to ValueView::_formatValue
	 *
	 * @type {globeCoordinate.Formatter}
	 */
	var formatter = new Formatter();

	var PARENT = vv.Expert;

	/**
	 * Valueview expert handling input of globe coordinate values.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.Expert
	 */
	vv.experts.GlobeCoordinateInput = vv.expert( 'GlobeCoordinateInput', PARENT, {
		/**
		 * Options.
		 * @type {Object}
		 */
		_options: {
			messages: {
				'valueview-expert-advancedadjustments': 'advanced adjustments',
				'valueview-expert-globecoordinateinput-precision': 'Precision'
			}
		},

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
		 * @type {jQuery.ui.preview}
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
			var self = this;

			this.$input = $( '<input/>', {
				type: 'text',
				'class': this.uiBaseClass + '-input valueview-input'
			} )
			.appendTo( this.$viewPort )
			.inputextender( {
				initCallback: function( $extension ) {
					self._initInputExtender( $extension );
					// $extension not yet in DOM, so draw() would not update rotators. Call draw
					// as soon as toggle has been done, from this point on the inputextender's
					// extension() is available in draw().
					self.$input.one( 'inputextenderaftertoggle', function( event ) {
						self.draw();
					} );
				},
				contentAnimationEvents: 'toggleranimation'
			} )
			.on( 'eachchange', function( event, oldValue ) {
				self._viewNotifier.notify( 'change' );
			} );
		},

		/**
		 * Initializes the input extender with the required content into the given DOM element.
		 *
		 * TODO: Split this up. Share code with similar experts (Time).
		 *
		 * @param {jQuery} $extension
		 */
		_initInputExtender: function( $extension ) {
			var self = this,
				precisionMsgKey = 'valueview-expert-globecoordinateinput-precision',
				precisionValues = [],
				listrotatorEvents = 'listrotatorauto listrotatorselected'
					.replace( /(\w+)/g, '$1.' + this.uiBaseClass );

			this.$precisionContainer = $( '<div/>' )
			.addClass( this.uiBaseClass + '-precisioncontainer' )
			.append( $( '<div/>' ).text( this._messageProvider.getMessage( precisionMsgKey ) ) );

			$.each( GlobeCoordinate.PRECISIONS, function( i, precision ) {
				var label = Formatter.PRECISIONTEXT( precision );
				precisionValues.push( {
					value: roundPrecision( precision ),
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

				self._viewNotifier.notify( 'change' );
			} )
			.appendTo( this.$precisionContainer );

			var $toggler = $( '<a/>' )
			.addClass( this.uiBaseClass + '-advancedtoggler' )
			.text( this._messageProvider.getMessage( 'valueview-expert-advancedadjustments' ) );

			var messageProvider = null;
			if( mediaWiki && mediaWiki.msg && util && util.MessageProvider ) {
				messageProvider = new util.MessageProvider( {
					messageGetter: mediaWiki.msg,
					prefix: 'valueview-preview-'
				} );
			}

			var $preview = $( '<div/>' ).preview( {
				$input: this.$input,
				messageProvider: messageProvider
			} );
			this.preview = $preview.data( 'preview' );

			// Append everything since the following actions require the fully initialized DOM.
			$extension.append( [
				$preview,
				$toggler,
				this.$precisionContainer
			] );
			this.$precision.data( 'listrotator' ).initWidths();
			this.$precisionContainer.css( 'display', 'none' );
			$toggler.toggler( { $subject: this.$precisionContainer } );
		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			if( !this.$input ) {
				return; // destroyed already
			}

			if( this.preview ) {
				this.preview.destroy();
				this.preview.element.remove();
			}

			var listRotator = this.$precision && this.$precision.data( 'listrotator' );
			if( listRotator ) {
				listRotator.destroy();
			}

			var inputExtender = this.$input.data( 'inputextender' );
			if( inputExtender ) {
				if( inputExtender.$extension ) {
					inputExtender.$extension.find( this.uiBaseClass + '-advancedtoggler' )
						.toggler( 'destroy' );
				}
				inputExtender.destroy();
			}

			this.$input.off( 'eachchange' );

			this.$input = null;
			this.$precision = null;
			this.$precisionContainer = null;

			PARENT.prototype.destroy.call( this ); // empties viewport
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
				rawValue = $( '<span/>' ).html( this.viewState().getFormattedValue() ).text();
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
				globeCoordinate1 = formatter.format( globeCoordinate1 );
			}

			if( globeCoordinate2 instanceof GlobeCoordinate ) {
				globeCoordinate2 = formatter.format( globeCoordinate2 );
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

			var considerInputExtender = this.$input.data( 'inputextender' ).extensionIsVisible();

			if( considerInputExtender
				&& (
					this._newValue
					|| this.$precision.data( 'listrotator' ).autoActive()
				)
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
			if( considerInputExtender ) {
				this.preview.update( $( '<span />').html( this.viewState().getFormattedValue() ).text() );
			}
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
	 * TODO: Calculated numbers used for the precision (e.g. 1/60) may result in different values
	 *  in front- and back-end. Either use dedicated float numbers in front- and back-end or
	 *  integrate the rounding in GlobeCoordinate.
	 *
	 * @since 0.1
	 *
	 * @param {number} precision
	 * @return {number}
	 */
	function roundPrecision( precision ) {
		var precisions = GlobeCoordinate.PRECISIONS,
			highestPrecision = precisions[precisions.length - 1],
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
			actualPrecision = null;

		$.each( GlobeCoordinate.PRECISIONS, function( i, precision ) {
			rounded = roundPrecision( precision );
			if( rounded === roundedPrecision ) {
				actualPrecision = precision;
				return false;
			}
		} );

		return actualPrecision;
	}

}( jQuery, jQuery.valueview, globeCoordinate.GlobeCoordinate, globeCoordinate.Formatter ) );
