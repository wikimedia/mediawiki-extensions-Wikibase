<template>
	<div
		class="wb-db-report-issue"
		v-html="message"
	/>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import StateMixin from '@/presentation/StateMixin';

export default defineComponent( {
	mixins: [ StateMixin ],
	name: 'ReportIssue',
	computed: {
		message(): string {
			return this.$messages.get(
				this.$messages.KEYS.ERROR_REPORT,
				this.rootModule.getters.issueReportingLinkConfig.replace(
					/<body>/g,
					encodeURIComponent( this.rootModule.getters.reportIssueTemplateBody ),
				),
				this.rootModule.state.pageUrl,
				this.rootModule.state.targetProperty,
				this.rootModule.state.entityTitle,
				this.rootModule.state.applicationErrors[ 0 ].type,
			);
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-report-issue {
	@include body-responsive();
	@include marginForCenterColumn();
	max-width: calc( 100% - 2 * #{$margin-center-column-side} ); // restrict text content to parent width minus margin
	overflow-wrap: break-word;

	@media ( max-width: $breakpoint ) {
		max-width: 100%; // margin is 0 on mobile (see marginForCenterColum() mixin)
	}

	p {
		// use margin shorthand to override all MediaWiki default margins, not just margin-bottom
		margin: 0 0 $base-spacing-unit 0;
	}

	li:not( :first-child ) {
		margin-top: $margin-top-li;
	}
}
</style>
