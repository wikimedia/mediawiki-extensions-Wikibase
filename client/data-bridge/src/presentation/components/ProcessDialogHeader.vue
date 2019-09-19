<template>
	<div class="wb-ui-processdialog-header">
		<!-- @slot This slot should be filled with a "safe" action, e.g. cancel -->
		<slot name="safeAction">
			<div />
		</slot>
		<h1 class="wb-ui-processdialog-header__title">
			{{ title }}
		</h1>
		<!-- @slot This slot should be filled with the primary action, e.g. publish -->
		<slot name="primaryAction">
			<div />
		</slot>
	</div>
</template>

<script lang="ts">
import Vue from 'vue';
import Component from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

@Component
export default class ProcessDialogHeader extends Vue {

	@Prop( { required: true, type: String } )
	public title!: string;
}
</script>

<style lang="scss">
.wb-ui-processdialog-header {
	display: flex;
	justify-content: space-between;
	min-height: $size-dialog-bar--desktop;

	&__title {
		display: inline-flex;
		align-items: center;
		font-size: $font-size-dialog-process-title;
		font-weight: bold;
	}
}
</style>
