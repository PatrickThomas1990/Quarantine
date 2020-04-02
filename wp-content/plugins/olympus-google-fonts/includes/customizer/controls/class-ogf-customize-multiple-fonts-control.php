<?php
/**
 * Fonts Custom Control
 *
 * @package   olympus-google-fonts
 * @copyright Copyright (c) 2020, Fonts Plugin
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Fonts control class.
 */
class OGF_Customize_Multiple_Fonts_Control extends WP_Customize_Control {

	/**
	 * The type of customize control being rendered.
	 *
	 * @var string
	 */
	public $type = 'typography-multiselect';

	/**
	 * Enqueue scripts/styles for the color picker.
	 */
	public function enqueue() {
		wp_enqueue_script( 'chosen', esc_url( OGF_DIR_URL . 'assets/js/chosen.min.js' ), array( 'jquery' ), OGF_VERSION, true );
	}

	/**
	 * Add custom parameters to pass to the JS via JSON.
	 */
	public function to_json() {
		parent::to_json();
		// The setting value.
		$this->json['id']    = $this->id;
		$this->json['value'] = $this->value();
		$this->json['link']  = $this->get_link();
	}

	/**
	 * Underscore JS template to handle the control's output.
	 */
	public function content_template() {
		?>
		<# if ( data.label ) { #>
			<span class="customize-control-title">{{ data.label }}</span>
		<# } #>

		<# if ( data.description ) { #>
			<span class="description customize-control-description">{{{ data.description }}}</span>
		<# } #>

		<# if ( typeof ogf_font_array != 'undefined' ) { #>
			<select data-placeholder="Choose some fonts..." multiple class="ogf-select" {{{ data.link }}}>
				<# _.each( ogf_font_array, function( font_data, font_id ) { #>
					<option value="{{ font_id }}">{{ font_data.family }}</option>
				<# } ) #>
			</select>
		<# } #>

		<?php
	}

}
