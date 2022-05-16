<template>
	<div class="wb-db-unsupported-datatype">
		<IconMessageBox
			class="wb-db-unsupported-datatype__message"
			type="notice"
			:inline="true"
		>
			<p
				class="wb-db-unsupported-datatype__head"
				v-html="$messages.get( $messages.KEYS.UNSUPPORTED_DATATYPE_ERROR_HEAD, propertyLabel )"
			/>
			<p
				class="wb-db-unsupported-datatype__body"
				v-html="$messages.get( $messages.KEYS.UNSUPPORTED_DATATYPE_ERROR_BODY, propertyLabel, dataType )"
			/>
		</IconMessageBox>
		<BailoutActions
			class="wb-db-unsupported-datatype__bailout"
			:original-href="originalHref"
			:page-title="pageTitle"
		/>
	</div>
</template>

<script lang="ts">
import { createApp, defineComponent, PropType } from 'vue';
import { DataType } from '@wmde/wikibase-datamodel-types';
import StateMixin from '@/presentation/StateMixin';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import BailoutActions from '@/presentation/components/BailoutActions.vue';
import TermLabel from '@/presentation/components/TermLabel.vue';

/**
 * A component used to illustrate a datatype error which happened when
 * the user tried to edit a value which bridge does not support yet.
 */
export default defineComponent( {
	mixins: [ StateMixin ],
	name: 'ErrorUnsupportedDatatype',
	components: {
		IconMessageBox,
		BailoutActions,
	},
	props: {
		dataType: {
			type: String as PropType<DataType>,
			required: true,
		},
	},
	computed: {
		pageTitle(): string {
			return this.rootModule.state.pageTitle;
		},
		originalHref(): string {
			return this.rootModule.state.originalHref;
		},
		propertyLabel(): HTMLElement {
			return createApp(
				TermLabel,
				{
					term: this.rootModule.getters.targetLabel,
					inLanguage: this.$inLanguage,
				},
			).mount( document.createElement( 'span' ) ).$el;
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-unsupported-datatype {
	@include errorBailout();
}
</style>
