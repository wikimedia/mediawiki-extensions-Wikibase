import ResizingTextField from '@/presentation/components/ResizingTextField.vue';

export default {
	title: 'ResizingTextField',
	component: ResizingTextField,
	docs: {
		extractComponentDescription: () => {
			// eslint-disable-next-line
			return 'Please note this case shows just the plain version of the component and has to be styled by its parent.';
		},
	},
};

export function plain() {
	return {
		data() { return { value: 'value' }; },
		components: { ResizingTextField },
		template: '<ResizingTextField :value="value" @input="value = $event" />',
	};
}

export function fullWidth() {
	return {
		data: () => ( {
			value: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. ' +
				'Nullam eu viverra ante. Sed eget quam mi. Duis at turpis eget odio cursus tincidunt. ' +
				'Donec blandit lorem vel eros ullamcorper laoreet. ' +
				'Pellentesque in dignissim nisl. ' +
				'Fusce pharetra, magna quis aliquet pellentesque, ' +
				'enim mi laoreet sapien, fermentum blandit magna metus sed lacus.',
		} ),
		components: { ResizingTextField },
		template:
			`<div>
				<p>
					This input occupies the full width of the surrounding window, minus some margin.
					Use the “open canvas in new tab” button to view just this story,
					then resize the window and see how the height of the text field adjusts itself automatically.
				</p>
				<ResizingTextField :value="value" @input="value = $event" style="width: 100%" />
			</div>`,
	};
}
