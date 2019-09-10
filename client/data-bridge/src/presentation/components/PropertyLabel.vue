<template>
	<label
		class="wb-db-PropertyLabel"
		:for="htmlFor"
		v-inlanguage="term.language"
	>{{ term.value }}</label>
</template>

<script lang="ts">
import {
	Component,
	Prop,
	Vue,
} from 'vue-property-decorator';
import Term from '@/datamodel/Term';

@Component( {} )
export default class PropertyLabel extends Vue {

	@Prop( { required: true } )
	public term!: Term;

	@Prop( { required: true } )
	public htmlFor!: string;
}
</script>

<style lang="scss">
.wb-db-PropertyLabel {
	@include inputFieldLabel();
	@include hyphens();
}
</style>
