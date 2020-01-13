<template>
	<div class="wb-tr-app">
		<div v-if="isTainted && !editState ">
			<span>
				<TaintedIcon :guid="id" />
				<div class="wb-tr-float-wrapper" v-if="popperIsOpened">
					<Popper :guid="id" :title="popperTitle">
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
							<p class="wb-tr-popper-text">
								{{ popperText }}
							</p>
							<p class="wb-tr-popper-feedback">
								{{ popperFeedbackText }}
								<a
									:title="popperFeedbackLinkTitle"
									:href="feedbackLink"
									target="_blank"
								>{{ popperFeedbackLinkText }}</a>
							</p>
						</template>
					</Popper>
				</div>
			</span>
		</div>
	</div>
</template>

<script lang="ts">
import {
	Component,
	Vue,
} from 'vue-property-decorator';
import TaintedIcon from '@/presentation/components/TaintedIcon.vue';
import { Getter } from 'vuex-class';
import Popper from '@/presentation/components/Popper.vue';

@Component( {
	components: {
		TaintedIcon,
		Popper,
	},
} )
export default class App extends Vue {
	@Getter( 'statementsTaintedState' )
	public statementsTaintedStateFunction!: Function;

	@Getter( 'popperState' )
	public popperStateFunction!: Function;

	@Getter( 'editState' )
	public editStateFunction!: Function;

	public get isTainted(): boolean {
		return this.statementsTaintedStateFunction( this.$data.id );
	}

	public get popperIsOpened(): boolean {
		return this.popperStateFunction( this.$data.id );
	}

	public get editState(): boolean {
		return this.editStateFunction( this.$data.id );
	}
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
	.wb-tr-app {
		display: inline-block;
		margin-top: 0.7em;
		padding-left: 0.5em;
	}

	.wb-tr-float-wrapper {
		display: block;
		position: absolute;
		margin-top: 15px;
		// Move left by 50% of its width, minus half the icon width
		transform: translateX( calc( -50% + 1.4em / 2 ) );
		z-index: 999;
	}

	.wb-tr-popper-feedback a {
		color: $link-blue;
	}

	.wb-tr-popper-feedback {
		font-weight: normal;
		color: $basic-text-black;
		margin: 8px 16px 8px 16px;
		line-height: 22px;
	}

	.wb-tr-popper-text {
		font-family: sans-serif;
		font-size: 14px;
		color: $basic-text-black;
		margin: 0 16px 8px 16px;
		line-height: 22px;
	}
</style>
