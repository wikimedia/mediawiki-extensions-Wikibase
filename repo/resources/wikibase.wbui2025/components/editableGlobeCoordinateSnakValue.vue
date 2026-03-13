<template>
	<wikibase-wbui2025-editable-no-value-some-value-snak-value
		:snak-key="snakKey"
		:removable="removable"
		:disabled="disabled"
		@remove-snak="$emit( 'remove-snak', snakKey )"
	>
		<cdx-text-input
			ref="inputElement"
			v-bind="$attrs"
			v-model="textvalue"
			inputmode="text"
			autocapitalize="off"
			autocorrect="off"
			spellcheck="false"
			:disabled="disabled"
			:class="activeClasses"
			@blur="onBlur"
			@focus="handleFocus"
		></cdx-text-input>
		<cdx-popover
			v-model:open="showPopover"
			class="wikibase-wbui2025-editable-globe-coordinate-popover"
			:use-close-button="true"
			:title="$i18n( 'wikibase-wbui2025-editable-snak-value-preview-label' ).text()"
			:anchor="inputElement"
			:disabled="disabled"
			placement="bottom-start"
			:hide-on-scroll="true"
			:render-in-place="true"
		>
			<div class="wikibase-coordinate-popover">
				<div class="wikibase-coordinate-popover__content">
					<div
						v-if="parseError"
						class="wikibase-coordinate-popover__malformed"
					>
						{{ $i18n( 'wikibase-parse-error' ) }}
					</div>

					<div
						v-else-if="formattedValue"
						class="wikibase-coordinate-popover__map"
					>
						<div
							ref="mapPlaceholder"
							class="wikibase-coordinate-popover__map-placeholder"
							v-html="formattedValue"
						></div>
					</div>

					<div v-else class="wikibase-coordinate-popover__loading"></div>
				</div>

				<div class="wikibase-coordinate-popover__precision">
					<div class="wikibase-coordinate-popover__precision-left">
						<div class="wikibase-coordinate-popover__precision-label">
							{{ $i18n( 'valueview-expert-globecoordinateinput-precision' ) }}
						</div>
						<div class="wikibase-coordinate-popover__precision-display">
							{{ precisionDisplayText }}
						</div>
					</div>

					<select
						v-model="selectedPrecision"
						class="wikibase-coordinate-popover__precision-select"
					>
						<option
							v-for="opt in precisionOptionsI18n"
							:key="opt.value"
							:value="opt.value"
						>
							{{ opt.label }}
						</option>
					</select>
				</div>
			</div>
		</cdx-popover>
	</wikibase-wbui2025-editable-no-value-some-value-snak-value>
</template>

<script>

const { computed, defineComponent, ref, nextTick } = require( 'vue' );
const { mapWritableState } = require( 'pinia' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const { CdxTextInput, CdxPopover } = require( '../../../codex.js' );
const WikibaseWbui2025EditableNoValueSomeValueSnakValue =
	require( './editableNoValueSomeValueSnakValue.vue' );

module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025EditableGlobeCoordinateSnakValue',
	components: {
		CdxTextInput,
		CdxPopover,
		WikibaseWbui2025EditableNoValueSomeValueSnakValue
	},
	inheritAttrs: false,
	props: {
		snakKey: { type: String, required: true },
		removable: { type: Boolean, default: false },
		disabled: { type: Boolean, required: true },
		className: {
			type: String,
			required: false,
			default: 'wikibase-wbui2025-editable-snak-value-input'
		}
	},
	emits: [ 'remove-snak' ],

	setup( props ) {
		const editSnakStoreGetter = wbui2025.store.useEditSnakStore( props.snakKey );
		const formattedValue = ref( wbui2025.store.snakValueHtmlForHash( editSnakStoreGetter().hash ) );
		const parseError = ref( false );
		const computedProperties = mapWritableState( editSnakStoreGetter, [
			'textvalue',
			'precision'
		] );
		const inputElement = ref();
		const formatValue = () => {
			editSnakStoreGetter().valueStrategy.getParsedValue().then( ( value ) => {
				if ( value === null ) {
					parseError.value = true;
					return;
				}
				wbui2025.api.renderSnakValueHtml( value, editSnakStoreGetter().property ).then( ( formatValueOutput ) => {
					parseError.value = false;
					formattedValue.value = formatValueOutput;
				} );
			} );
		};

		return {
			inputElement,
			formattedValue,
			parseError,
			textvalue: computed( computedProperties.textvalue ),
			precision: computed( computedProperties.precision ),
			debouncedTriggerFormatAndParse: mw.util.debounce( formatValue, 300 )
		};
	},
	data() {
		return {
			inputHadFocus: false,
			showPopover: false,
			selectedPrecision: 'auto',
			precisionOptionsI18n: [
				{
					value: 'auto',
					label: mw.msg( 'wikibase-wbui2025-globecoordinateinput-precision-automatic' )
				},
				{
					value: 1 / 3600000,
					label: mw.msg( 'valueview-expert-globecoordinateinput-precisionlabel-thousandth-of-arcsecond' )
				},
				{
					value: 1 / 360000,
					label: mw.msg( 'valueview-expert-globecoordinateinput-precisionlabel-hundredth-of-arcsecond' )
				},
				{
					value: 1 / 36000,
					label: mw.msg( 'valueview-expert-globecoordinateinput-precisionlabel-tenth-of-arcsecond' )
				},
				{
					value: 1 / 3600,
					label: mw.msg( 'valueview-expert-globecoordinateinput-precisionlabel-arcsecond' )
				},
				{
					value: 1 / 60,
					label: mw.msg( 'valueview-expert-globecoordinateinput-precisionlabel-arcminute' )
				},
				{ value: 0.000001, label: '±0.000001°' },
				{ value: 0.00001, label: '±0.00001°' },
				{ value: 0.0001, label: '±0.0001°' },
				{ value: 0.001, label: '±0.001°' },
				{ value: 0.01, label: '±0.01°' },
				{ value: 0.1, label: '±0.1°' },
				{ value: 1, label: '±1°' },
				{ value: 10, label: '±10°' }
			]
		};
	},
	computed: {
		activeClasses() {
			return [
				{ 'cdx-text-input--status-error': this.inputHadFocus && this.parseError },
				this.className
			];
		},

		// TODO: display the derived precision when automatic (T419586)
		precisionDisplayText() {
			const selected = this.precisionOptionsI18n.find(
				( o ) => o.value === this.precision
			);
			if ( !selected ) {
				return '';
			}
			return selected.label;
		}
	},

	methods: {
		// eslint-disable-next-line vue/no-unused-properties
		focus() {
			this.$refs.inputElement.focus();
		},
		handleFocus() {
			this.showPopover = true;
		},
		onBlur() {
			this.inputHadFocus = true;
		}
	},

	watch: {
		showPopover: {
			immediate: true,
			handler( newValue ) {
				if ( newValue && !!this.textvalue ) {
					nextTick().then( () => {
						wbui2025.util.initKartographerPreview( this.$refs.mapPlaceholder );
					} );
				}
			}
		},

		textvalue: {
			immediate: true,
			handler( newValue ) {
				if ( newValue === undefined ) {
					return;
				}
				this.debouncedTriggerFormatAndParse();
			}
		},

		selectedPrecision: {
			immediate: true,
			handler( newPrecision ) {
				this.precision = ( newPrecision === 'auto' ? undefined : newPrecision );
				this.debouncedTriggerFormatAndParse();
			}
		},

		formattedValue: {
			immediate: true,
			handler( newFormattedValue ) {
				if ( !!newFormattedValue && this.showPopover ) {
					nextTick().then( () => {
						wbui2025.util.initKartographerPreview( this.$refs.mapPlaceholder );
					} );
				}
			}
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-editable-globe-coordinate-popover {
	.cdx-popover__header__title {
		font-weight: @font-weight-normal;
	}
}

.wikibase-coordinate-popover {
	width: 327px;
}

.wikibase-coordinate-popover__title {
	color: @color-subtle;
}

.wikibase-coordinate-popover__malformed {
	color: @color-base;
	font-weight: normal;
	font-size: 24px;
	line-height: 1.2;
}

.wikibase-coordinate-popover__loading {
	min-height: 180px;
}

.wikibase-coordinate-popover__map-placeholder {
	min-height: 180px;
	border: 1px solid @border-color-subtle;
	background: @background-color-interactive-subtle;
	width: 100%;

	.mw-parser-output {
		margin: 0;
	}

	.mw-kartographer-map.floatleft,
	a.mw-kartographer-map.floatleft {
		float: none !important;
	}

	.mw-kartographer-map,
	a.mw-kartographer-map {
		display: block;
		width: 100% !important;
		max-width: 100%;
	}
}

.wikibase-coordinate-popover__precision {
	margin-top: 12px;
	display: flex;
	align-items: center;
	gap: 8px;
}

.wikibase-coordinate-popover__precision-label {
	font-weight: bold;
}

.wikibase-coordinate-popover__precision-display {
	color: @color-progressive;
}

.wikibase-coordinate-popover__precision-select {
	font-size: 12px;
}
</style>
