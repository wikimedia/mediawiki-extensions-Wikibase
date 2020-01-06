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
		margin-top: 8px;
		margin-bottom: 8px;
	}

	.wb-tr-popper__text--top {
		margin-top: 4px;
	}
</style>
