<template>
	<p>
		Target Value: {{ targetValue }}
	</p>
</template>

<script lang="ts">
import {
	Component,
	Vue,
	Prop,
} from 'vue-property-decorator';

@Component
export default class DataPlaceholder extends Vue {
	@Prop( {
		required: true,
	} )
	public targetValue!: null|string;
}
</script>

<style scoped></style>
