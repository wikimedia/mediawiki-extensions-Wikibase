<template>
	<div class="wb-db-unsupported-snaktype">
		<IconMessageBox
			class="wb-db-unsupported-snaktype__message"
			type="notice"
			:inline="true"
		>
			<p class="wb-db-unsupported-snaktype__head">
				{{ $messages.get(
					messageHeaderKey,
					targetProperty,
				) }}
			</p>
			<p
				class="wb-db-unsupported-snaktype__body"
				v-html="$messages.get( messageBodyKey, targetProperty )"
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
import { Prop } from 'vue-property-decorator';
import Component, { mixins } from 'vue-class-component';
import StateMixin from '@/presentation/StateMixin';
import { SnakType } from '@/datamodel/Snak';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import BailoutActions from '@/presentation/components/BailoutActions.vue';

/**
 * A component used to illustrate an error which happened when the user tried
 * to edit a statement with a snak type not supported by Bridge yet.
 */
@Component( {
	components: {
		IconMessageBox,
		BailoutActions,
	},
} )
export default class ErrorUnsupportedSnakType extends mixins( StateMixin ) {
	public get targetProperty(): string {
		return this.rootModule.state.targetProperty;
	}

	public get pageTitle(): string {
		return this.rootModule.state.pageTitle;
	}

	public get originalHref(): string {
		return this.rootModule.state.originalHref;
	}

	@Prop( { required: true } )
	public snakType!: SnakType;

	private get messageHeaderKey(): string {
		switch ( this.snakType ) {
			case 'somevalue':
				return this.$messages.KEYS.SOMEVALUE_ERROR_HEAD;
			case 'novalue':
				return this.$messages.KEYS.NOVALUE_ERROR_HEAD;
			default:
				throw new Error( `No message for unsupported snak type ${this.snakType}` );
		}
	}

	private get messageBodyKey(): string {
		switch ( this.snakType ) {
			case 'somevalue':
				return this.$messages.KEYS.SOMEVALUE_ERROR_BODY;
			case 'novalue':
				return this.$messages.KEYS.NOVALUE_ERROR_BODY;
			default:
				throw new Error( `No message for unsupported snak type ${this.snakType}` );
		}
	}
}
</script>

<style lang="scss">
.wb-db-unsupported-snaktype {
	@include errorBailout();

	&__body i { // <i>some value</i> or <i>no value</i> in message
		font-style: italic;
	}
}
</style>
