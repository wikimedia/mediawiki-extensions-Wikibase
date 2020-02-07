<template>
	<span
		class="wb-db-term-label"
		v-inlanguage="term.language"
	>{{ term.value }}</span>
</template>

<script lang="ts">
import Vue from 'vue';
import Component from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import Term from '@/datamodel/Term';

/**
 * A label for a Term in a certain language.
 */
@Component
export default class TermLabel extends Vue {
	@Prop( { required: true } )
	public term!: Term;
}
</script>

<style lang="scss">
.wb-db-term-label {
	@include hyphens();
}
</style>
