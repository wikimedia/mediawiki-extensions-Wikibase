<template>
	<p
		class="wb-db-report-issue"
		v-html="message"
	/>
</template>

<script lang="ts">
import Component, { mixins } from 'vue-class-component';
import StateMixin from '@/presentation/StateMixin';

@Component
export default class ReportIssue extends mixins( StateMixin ) {
	public get message(): string {
		return this.$messages.get(
			this.$messages.KEYS.ERROR_REPORT,
			this.$bridgeConfig.issueReportingLink.replace(
				/<body>/g,
				encodeURIComponent( this.rootModule.getters.reportIssueTemplateBody ),
			),
			this.rootModule.state.pageUrl,
			this.rootModule.state.targetProperty,
			this.entityModule.state.id,
			this.rootModule.state.applicationErrors[ 0 ].type,
		);
	}
}
</script>

<style lang="scss">
.wb-db-report-issue {
	@include body-responsive();
	@include marginForCenterColumn();
	max-width: calc( 100% - 2 * #{$margin-center-column-side} );
	overflow-x: auto;

	p {
		// use margin shorthand to override all MediaWiki default margins, not just margin-bottom
		margin: 0 0 $base-spacing-unit 0;
	}

	li:not( :first-child ) {
		margin-top: $margin-top-li;
	}
}
</style>
