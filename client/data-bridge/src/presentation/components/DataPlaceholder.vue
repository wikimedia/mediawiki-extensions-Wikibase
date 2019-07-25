<template>
	<div>
		<ul>
			<li>Entity ID: <strong id="data-bridge-entityId">{{ entityId }}</strong></li>
			<li>Property ID: <strong id="data-bridge-propertyId">{{ propertyId }}</strong></li>
			<li>edit flow: <strong id="data-bridge-editFlow">{{ editFlow }}</strong></li>
		</ul>
	</div>
</template>

<script lang="ts">
export default {
	name: 'DataPlaceholder',
	props: {
		entityId: {
			type: String,
			required: true,
		},
		propertyId: {
			type: String,
			required: true,
		},
		editFlow: {
			type: String,
			required: true,
		},
	},
};
</script>

<style scoped></style>
