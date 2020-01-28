<template>
	<Popper :guid="guid" :title="popperTitle">
		<template v-slot:subheading-area>
			<div class="wb-tr-popper-help">
				<a
					:title="popperHelpLinkTitle"
					:href="helpLink"
					target="_blank"
					@click="helpClick"
				>{{ popperHelpLinkText }}</a>
			</div>
		</template>
		<template v-slot:content>
			<p class="wb-tr-popper__text wb-tr-popper__text--top">
				{{ popperText }}
			</p>
			<button class="wb-tr-popper-remove-warning" @click="removeWarningClick">
				{{ removeWarningText }}
			</button>
			<p class="wb-tr-popper__text wb-tr-popper-feedback">
				{{ popperFeedbackText }}
				<a
					:title="popperFeedbackLinkTitle"
					:href="feedbackLink"
					target="_blank"
				>{{ popperFeedbackLinkText }}</a>
			</p>
		</template>
	</Popper>
</template>

<script lang="ts">
import { STATEMENT_TAINTED_STATE_UNTAINT } from '@/store/actionTypes';
import { Getter } from 'vuex-class';
import {
	Component,
	Vue,
} from 'vue-property-decorator';
import Popper from '@/presentation/components/Popper.vue';

@Component( {
	props: {
		guid: String,
		title: String,
	},
	components: {
		Popper,
	},
} )
export default class TaintedPopper extends Vue {
	public get popperTitle(): string {
		return this.$message( 'wikibase-tainted-ref-popper-title' );
	}

	public get popperHelpLinkTitle(): string {
		return this.$message( 'wikibase-tainted-ref-popper-help-link-title' );
	}

	public get popperHelpLinkText(): string {
		return this.$message( 'wikibase-tainted-ref-popper-help-link-text' );
	}

	public helpClick(): void {
		this.$track( 'counter.wikibase.view.tainted-ref.helpLinkClick', 1 );
	}

	@Getter( 'helpLink' )
	public helpLink!: string;

	public get popperText(): string {
		return this.$message( 'wikibase-tainted-ref-popper-text' );
	}

	public get removeWarningText(): string {
		return this.$message( 'wikibase-tainted-ref-popper-remove-warning' );
	}

	public get popperFeedbackText(): string {
		return this.$message( 'wikibase-tainted-ref-popper-feedback-text' );
	}

	public get popperFeedbackLinkText(): string {
		return this.$message( 'wikibase-tainted-ref-popper-feedback-link-text' );
	}

	public get popperFeedbackLinkTitle(): string {
		return this.$message( 'wikibase-tainted-ref-popper-feedback-link-title' );
	}

	@Getter( 'feedbackLink' )
	public feedbackLink!: string;

	public removeWarningClick( _event: MouseEvent ): void {
		this.$track( 'counter.wikibase.view.tainted-ref.removeWarningClick', 1 );
		this.$store.dispatch( STATEMENT_TAINTED_STATE_UNTAINT, this.$props.guid );
	}
}
</script>

<style lang="scss">
	.wb-tr-popper-feedback a,
	.wb-tr-popper-help a {
		color: $link-blue;
	}

	.wb-tr-popper__text {
		font-weight: normal;
		font-family: sans-serif;
		font-size: 14px;
		color: $basic-text-black;
		line-height: 22px;
		margin: 0 0 8px 0;
	}

	.wb-tr-popper__text--top {
		margin-top: 4px;
	}

	.wb-tr-popper-feedback a {
		color: $link-blue;
	}

	.wb-tr-popper-feedback {
		font-weight: normal;
		color: $basic-text-black;
		margin: 8px 0 8px 0;
		line-height: 22px;
	}

	.wb-tr-popper-remove-warning {
		color: $color-light-grey;
		font-family: sans-serif;
		font-size: 14px;
		font-weight: bold;
		border: solid 1px $border-color;
		border-radius: 2px;
		background: $background-color-light-grey;
		padding: 4px 16px 4px 16px;
	}

	.wb-tr-popper-remove-warning:hover {
		background: $background-color-white;
	}

	.wb-tr-popper-remove-warning:focus {
		background: $background-color-white;
		border-color: $border-color-focus;
	}

	.wb-tr-popper-remove-warning:active {
		color: $color-black;
		background: $background-color-active;
		border-color: $border-color-active;
	}
</style>
