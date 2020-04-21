<template>
	<ProcessDialogHeader class="wb-db-app__header">
		<template v-slot:title>
			<span v-html="title" />
		</template>
		<template v-slot:primaryAction>
			<EventEmittingButton
				:message="$messages.get( publishOrSave )"
				type="primaryProgressive"
				size="L"
				:squary="true"
				@click="save"
				:disabled="!canStartSaving"
				v-if="!hasError && !isSaved"
			/>
		</template>
		<template v-slot:safeAction>
			<EventEmittingButton
				:message="$messages.get( $messages.KEYS.CANCEL )"
				type="close"
				size="L"
				:squary="true"
				:disabled="isSaving"
				@click="close"
			/>
		</template>
	</ProcessDialogHeader>
</template>

<script lang="ts">
import Component, { mixins } from 'vue-class-component';
import ProcessDialogHeader from '@/presentation/components/ProcessDialogHeader.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import StateMixin from '@/presentation/StateMixin';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import TermLabel from '@/presentation/components/TermLabel.vue';

@Component( {
	components: {
		EventEmittingButton,
		ProcessDialogHeader,
	},
} )
export default class AppHeader extends mixins( StateMixin ) {

	public get title(): string {
		return this.$messages.get(
			this.$messages.KEYS.BRIDGE_DIALOG_TITLE,
			new TermLabel( {
				propsData: {
					term: this.rootModule.getters.targetLabel,
				},
			} ).$mount().$el as HTMLElement,
		);
	}

	public get isSaved(): boolean {
		return this.rootModule.getters.applicationStatus === ApplicationStatus.SAVED;
	}

	public get hasError(): boolean {
		return this.rootModule.getters.applicationStatus === ApplicationStatus.ERROR;
	}

	public get publishOrSave(): string {
		return this.$bridgeConfig.usePublish ?
			this.$messages.KEYS.PUBLISH_CHANGES : this.$messages.KEYS.SAVE_CHANGES;
	}

	public get canStartSaving(): boolean {
		return this.rootModule.getters.canStartSaving;
	}

	public get isSaving(): boolean {
		return this.rootModule.getters.applicationStatus === ApplicationStatus.SAVING;
	}

	public close(): void {
		this.$emit( 'close' );
	}

	public save(): void {
		this.$emit( 'save' );
	}

}
</script>
