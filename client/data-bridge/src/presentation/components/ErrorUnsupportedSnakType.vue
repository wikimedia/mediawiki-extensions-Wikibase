<template>
	<div class="wb-db-unsupported-snaktype">
		<IconMessageBox
			class="wb-db-unsupported-snaktype__message"
			type="notice"
			:inline="true"
		>
			<p
				class="wb-db-unsupported-snaktype__head"
				v-html="$messages.get( messageHeaderKey, propertyLabel )"
			/>
			<p
				class="wb-db-unsupported-snaktype__body"
				v-html="$messages.get( messageBodyKey, propertyLabel )"
			/>
		</IconMessageBox>
		<BailoutActions
			class="wb-db-unsupported-snaktype__bailout"
			:original-href="originalHref"
			:page-title="pageTitle"
		/>
	</div>
</template>

<script lang="ts">
import { createApp, defineComponent, PropType } from 'vue';
import StateMixin from '@/presentation/StateMixin';
import { SnakType } from '@wmde/wikibase-datamodel-types';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import BailoutActions from '@/presentation/components/BailoutActions.vue';
import TermLabel from '@/presentation/components/TermLabel.vue';

/**
 * A component used to illustrate an error which happened when the user tried
 * to edit a statement with a snak type not supported by Bridge yet.
 */
export default defineComponent( {
	mixins: [ StateMixin ],
	name: 'ErrorUnsupportedSnakType',
	components: {
		IconMessageBox,
		BailoutActions,
	},
	props: {
		snakType: {
			type: String as PropType<SnakType>,
			required: true,
		},
	},
	computed: {
		propertyLabel(): HTMLElement {
			return createApp(
				TermLabel,
				{
					term: this.rootModule.getters.targetLabel,
					inLanguage: this.$inLanguage,
				},
			).mount( document.createElement( 'span' ) ).$el;
		},
		pageTitle(): string {
			return this.rootModule.state.pageTitle;
		},
		originalHref(): string {
			return this.rootModule.state.originalHref;
		},
		messageHeaderKey(): string {
			switch ( this.snakType ) {
				case 'somevalue':
					return this.$messages.KEYS.SOMEVALUE_ERROR_HEAD;
				case 'novalue':
					return this.$messages.KEYS.NOVALUE_ERROR_HEAD;
				default:
					throw new Error( `No message for unsupported snak type ${this.snakType}` );
			}
		},
		messageBodyKey(): string {
			switch ( this.snakType ) {
				case 'somevalue':
					return this.$messages.KEYS.SOMEVALUE_ERROR_BODY;
				case 'novalue':
					return this.$messages.KEYS.NOVALUE_ERROR_BODY;
				default:
					throw new Error( `No message for unsupported snak type ${this.snakType}` );
			}
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-unsupported-snaktype {
	@include errorBailout();
}
</style>
