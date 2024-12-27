document.addEventListener('DOMContentLoaded', () => {
	const DEFAULT_COLOR = '#000000';
	const HEX_REGEX = /^#([A-Fa-f0-9]{6})$/;

	const setupColorField = (textId, pickerId) => {
		const colorText = document.getElementById(textId);
		const colorPicker = document.getElementById(pickerId);

		if (!colorText || !colorPicker) {
			return;
		}

		const syncColors = (source, target) => {
			const value = source.value.trim();
			target.value = HEX_REGEX.test(value) ? value : DEFAULT_COLOR;
		};

		const handleInput = ({ target }) => {
			if (target === colorPicker) {
				colorText.value = target.value;
			} else {
				syncColors(colorText, colorPicker);
			}
		};

		colorPicker.addEventListener('input', handleInput);
		colorText.addEventListener('input', handleInput);
		colorText.addEventListener('blur', () => {
			if (!colorText.value.trim()) {
				colorPicker.value = DEFAULT_COLOR;
			}
		});

		syncColors(colorText, colorPicker);
	};

	['theme', 'bg'].forEach(type =>
		setupColorField(`pwa_${type}_color`, `pwa_${type}_color_picker`)
	);
});
