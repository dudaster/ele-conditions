<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Elecond_Datetime_Control extends \Elementor\Base_Data_Control {

	const TYPE = 'elecond_datetime';

	public function get_type() {
		return self::TYPE;
	}

	public function get_default_value() {
		return '';
	}

	public function enqueue() {
		wp_add_inline_style( 'elementor-editor', '
			.elementor-control-type-elecond_datetime input[type="datetime-local"] {
				width: 100%;
				padding: 7px 10px;
				border: 1px solid #d5d5d5;
				border-radius: 3px;
				font-size: 12px;
				color: #555d6b;
				background: #fff;
				box-sizing: border-box;
				cursor: pointer;
			}
			.elementor-control-type-elecond_datetime input[type="datetime-local"]:focus {
				border-color: #93003c;
				outline: none;
				box-shadow: 0 0 0 1px #93003c33;
			}
			.elementor-control-type-elecond_datetime input[type="datetime-local"]::-webkit-calendar-picker-indicator {
				cursor: pointer;
				opacity: 0.6;
			}
			.elementor-control-type-elecond_datetime input[type="datetime-local"]::-webkit-calendar-picker-indicator:hover {
				opacity: 1;
			}
		' );
	}

	public function content_template() {
		$control_uid = $this->get_control_uid();
		?>
		<div class="elementor-control-field">
			<# if ( data.label ) { #>
			<label for="<?php echo esc_attr( $control_uid ); ?>" class="elementor-control-title">{{{ data.label }}}</label>
			<# } #>
			<div class="elementor-control-input-wrapper">
				<input
					id="<?php echo esc_attr( $control_uid ); ?>"
					type="datetime-local"
					class="elementor-input"
					data-setting="{{ data.name }}"
				>
			</div>
		</div>
		<# if ( data.description ) { #>
		<div class="elementor-control-field-description">{{{ data.description }}}</div>
		<# } #>
		<?php
	}
}
