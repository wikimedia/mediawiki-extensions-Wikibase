import RadioInput from '@/presentation/components/RadioInput.vue';

export default {
	title: 'RadioInput',
	component: RadioInput,
	parameters: {
		controls: { hideNoControlsWarning: true },
	},
};

export function normal() {
	return {
		components: { RadioInput },
		template:
			`<form>
				<RadioInput
						name="input name"
						htmlValue="input value"
				>
					<template #label>Option 1</template>
				</RadioInput>
			</form>`,
	};
}

export function defaultChecked() {
	return {
		components: { RadioInput },
		template:
			`<form>
				<RadioInput
						name="input name"
						htmlValue="input value"
						value="input value"
				>
					<template #label>Option 1</template>
				</RadioInput>
			</form>`,
	};
}

export function disabled() {
	return {
		components: { RadioInput },
		template:
			`<form>
				<RadioInput
						name="input name"
						htmlValue="input value"
						:disabled="true"
				>
					<template #label>Option 1</template>
				</RadioInput>
			</form>`,
	};
}

export function disabledChecked() {
	return {
		components: { RadioInput },
		template:
			`<form>
				<RadioInput
						name="input name"
						htmlValue="input value"
						value="input value"
						:disabled="true"
				>
					<template #label>Option 1</template>
				</RadioInput>
			</form>`,
	};
}

export function withDescription() {
	return {
		components: { RadioInput },
		template:
			`<form>
				<RadioInput
						name="input name"
						htmlValue="input value"
				>
					<template #label>Option 1</template>
					<template #description>Some additional text</template>
				</RadioInput>
			</form>`,
	};
}

export function withLongLabelAndDescription() {
	return {
		components: { RadioInput },
		template:
			`<form style="max-width: 40em;">
				<RadioInput
						name="input name"
						htmlValue="input value"
				>
					<template #label><strong>Lorem ipsum</strong> dolor sit amet, consectetur adipiscing elit. Nullam eu viverra ante. Sed eget quam mi. Duis at turpis eget odio cursus tincidunt.</template>
					<template #description><strong>Donec blandit</strong> lorem vel eros ullamcorper laoreet. Pellentesque in dignissim nisl. Fusce pharetra, magna quis aliquet pellentesque, enim mi laoreet sapien, fermentum blandit magna metus sed lacus.</template>
				</RadioInput>
			</form>`,
	};
}

export function multipleInputs() {
	return {
		components: { RadioInput },
		data: () => ( { picked: 'none' } ),
		template:
			`<form>
				<p>Picked: {{ picked }}</p>
				<RadioInput
						name="input name"
						htmlValue="input value 1"
						v-model="picked"
				>
					<template #label>Option 1</template>
				</RadioInput>

				<RadioInput
						name="input name"
						htmlValue="input value 2"
						v-model="picked"
				>
					<template #label>Option 2</template>
					<template #description>Some description text</template>
				</RadioInput>

				<RadioInput
						name="input name"
						htmlValue="input value 3"
						v-model="picked"
				>
					<template #label>Option 3</template>
				</RadioInput>

				<RadioInput
						name="input name"
						htmlValue="input value 4"
						:disabled="true"
						v-model="picked"
				>
					<template #label>Option 4 (disabled)</template>
				</RadioInput>

				<br>
				<p><small>(vertical spacing must be handled by the client application)</small></p>
			</form>`,
	};
}

export function withLinksInLabelAndDescription() {
	return {
		components: { RadioInput },
		template:
			`<form>
				<RadioInput
						name="input name"
						htmlValue="input value"
				>
					<template #label>Option 1 (<a href="https://example.com">details</a>)</template>
					<template #description>Some additional text (<a href="https://example.com" target="_blank">more details</a>)
					</template>
				</RadioInput>
			</form>`,
	};
}

export function differentSizes() {
	return {
		components: { RadioInput },
		template:
			`<form>
				<div style="font-size: 1rem">
					<p>
						The input’s text should adjust to the size of its surroundings.
						This one would have a normal size.
					</p>
					<RadioInput
							name="input name"
							htmlValue="input value 1"
					>
						<template #label>Option label</template>
						<template #description>Additional description with more details</template>
					</RadioInput>
				</div>
				<div style="font-size: 2rem">
					<p>
						Whereas this one should be huge by comparison.
					</p>
					<RadioInput
							name="input name"
							htmlValue="input value 2"
					>
						<template #label>Option label</template>
						<template #description>Additional description with more details</template>
					</RadioInput>
				</div>
			</form>`,
	};
}
