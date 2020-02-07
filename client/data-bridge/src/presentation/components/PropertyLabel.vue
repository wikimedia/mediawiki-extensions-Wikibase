<template>
	<label
		class="wb-db-property-label"
		:for="htmlFor"
	><TermLabel :term="term" /></label>
</template>

<script lang="ts">
import TermLabel from '@/presentation/components/TermLabel.vue';
import {
	Component,
	Prop,
	Vue,
} from 'vue-property-decorator';
import Term from '@/datamodel/Term';

@Component( {
	components: { TermLabel },
} )
export default class PropertyLabel extends Vue {

	@Prop( { required: true } )
	public term!: Term;

	@Prop( { required: true } )
	public htmlFor!: string;
}
</script>

<style lang="scss">
.wb-db-property-label {
	@include inputFieldLabel();
}
</style>
