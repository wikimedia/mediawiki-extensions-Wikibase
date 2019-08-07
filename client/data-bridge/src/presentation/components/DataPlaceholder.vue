<template>
	<p class="wb-db-placeholder">
		Target Value:
		<span class="wb-db-placeholder__value">{{ targetValue }}</span>
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
