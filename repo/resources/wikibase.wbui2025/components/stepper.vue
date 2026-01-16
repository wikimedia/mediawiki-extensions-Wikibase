<template>
	<div class="wikibase-wbui2025-stepper">
		<span
			class="wikibase-wbui2025-stepper-label"
			v-html="$i18n( 'wikibase-stepper-progress', currentStep, totalSteps )">
		</span>
		<span
			v-for="step in totalSteps"
			:key="step"
			class="wikibase-wbui2025-stepper-step"
			:class="{
				'wikibase-wbui2025-stepper-step__previous': step < currentStep,
				'wikibase-wbui2025-stepper-step__active': step === currentStep
			}"
		></span>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025Stepper',
	props: {
		currentStep: {
			type: Number,
			required: false,
			default: 1
		},
		totalSteps: {
			type: Number,
			required: true
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-stepper {
	display: flex;
	flex-direction: row;
	gap: @spacing-50;
	color: @color-subtle;
	font-size: @font-size-small;
	align-items: center;
}

.wikibase-wbui2025-stepper-step {
	border-radius: @border-radius-circle;
	background-color: @color-disabled;
	width: @size-50;
	height: @size-50;
}

.wikibase-wbui2025-stepper-step__active {
	background-color: @color-base;
}

.wikibase-wbui2025-stepper-step__previous {
	background-color: @color-subtle;
}
</style>
