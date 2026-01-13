<?php
namespace JET_OCC;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Icons_Manager;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Elementor {

	private $initialized = false;

	public function __construct() {

		add_action('wp_enqueue_scripts', [ $this, 'add_styles' ] );

		add_action(
			'elementor/element/column/section_advanced/after_section_end',
			[ $this, 'add_settings' ], 10, 2
		);

		add_action(
			'elementor/element/container/section_layout/after_section_end',
			[ $this, 'add_settings' ], 10, 2
		);

		add_action( 'elementor/frontend/column/before_render', [ $this, 'before_element_render' ] );
		add_action( 'elementor/frontend/container/before_render', [ $this, 'before_element_render' ] );

		add_filter( 'elementor/element/is_dynamic_content', [ $this, 'maybe_set_element_as_dynamic' ], 10, 2 );

	}

	public function maybe_set_element_as_dynamic( $result, $data ) {
		if ( empty( $data['settings']['jet_offcanvas_enabled'] ) ) {
			return $result;
		}

		$offcanvas = ! empty( $data['settings']['jet_offcanvas_enabled'] ) ? $data['settings']['jet_offcanvas_enabled'] : false;
		$offcanvas = filter_var( $offcanvas, FILTER_VALIDATE_BOOLEAN );

		return $offcanvas || $result;
	}

	public function before_element_render( $element ) {

		$settings = $element->get_settings_for_display();
		$offcanvas = ! empty( $settings['jet_offcanvas_enabled'] ) ? $settings['jet_offcanvas_enabled'] : false;
		$offcanvas = filter_var( $offcanvas, FILTER_VALIDATE_BOOLEAN );

		if ( ! $offcanvas ) {
			return;
		}

		$expand = ! empty( $settings['jet_offcanvas_expand_text'] ) ? wp_kses_post( $settings['jet_offcanvas_expand_text'] ) : '';
		$collapse = ! empty( $settings['jet_offcanvas_collapse_text'] ) ? wp_kses_post( $settings['jet_offcanvas_collapse_text'] ) : '';

		if ( ! empty( $settings['jet_offcanvas_expand_icon'] ) && ! empty( $settings['jet_offcanvas_expand_icon']['value'] ) ) {
			$expand = $this->add_icon( $settings['jet_offcanvas_expand_icon'], $expand );
		}

		if ( ! empty( $settings['jet_offcanvas_collapse_icon'] ) && ! empty( $settings['jet_offcanvas_collapse_icon']['value'] ) ) {
			$collapse = $this->add_icon( $settings['jet_offcanvas_collapse_icon'], $collapse );
		}

		$element->add_render_attribute( '_wrapper', 'class', 'jet-offcanvas' );
		$element->add_render_attribute( '_wrapper', 'data-jet-offcanvas', htmlspecialchars( json_encode( [
			'expand' => $expand,
			'collapse' => $collapse,
		] ) ) );

		if ( ! $this->initialized ) {
			add_action( 'wp_footer', [ $this, 'js_handler' ] );
			$this->initialized = true;
		}

	}

	public function add_icon( $icon, $text ) {
		// Validate icon data before rendering
		if ( empty( $icon ) || ! is_array( $icon ) || empty( $icon['value'] ) ) {
			return $text;
		}

		ob_start();
		Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );
		$icon_html = ob_get_clean();

		// Only add icon wrapper if we got valid output
		if ( ! empty( $icon_html ) ) {
			return '<div class="jet-offcanvas-icon">' . $icon_html . '</div>' . $text;
		}

		return $text;
	}

	public function js_handler() {
		?>
<script type="text/javascript">
(function() {
	'use strict';

	function initOffcanvas() {
		try {
			const offcanavs = document.querySelectorAll('.jet-offcanvas');
			console.log('Jet Offcanvas: Found ' + offcanavs.length + ' offcanvas elements');

			offcanavs.forEach( ( offcanv ) => {

				if ( offcanv.dataset.jetOffcanvasInitialized ) {
					console.log('Jet Offcanvas: Element already initialized, skipping');
					return;
				}

				if (!offcanv.dataset.jetOffcanvas) {
					console.error('Jet Offcanvas: Element missing data-jet-offcanvas attribute', offcanv);
					return;
				}

				console.log('Jet Offcanvas: Initializing element with data:', offcanv.dataset.jetOffcanvas);

				offcanv.dataset.jetOffcanvasInitialized = true;

				let parent = offcanv.parentNode;
						let settings = JSON.parse( offcanv.dataset.jetOffcanvas );
						let expandNode = document.createElement( 'div' );
						let collapseNode = document.createElement( 'div' );

						expandNode.classList.add( 'jet-offcanvas-trigger-wrap' );
						collapseNode.classList.add( 'jet-offcanvas-trigger-wrap' );
						expandNode.classList.add( 'jet-offcanvas-expand-wrap' );
						collapseNode.classList.add( 'jet-offcanvas-collapse-wrap' );

						expandNode.classList.add( 'jet-offcanvas-' + offcanv.dataset.id );

						expandNode.innerHTML = '<div class="jet-offcanvas-expand jet-offcanvas-trigger" tabindex="0">' + settings.expand + '</div>';
						collapseNode.innerHTML = '<div class="jet-offcanvas-collapse jet-offcanvas-trigger" tabindex="0">' + settings.collapse + '</div>';


						function applyMobileStyles() {
							if (window.innerWidth <= 767 || document.body.getAttribute('data-elementor-device-mode') === 'mobile' || document.body.getAttribute('data-elementor-device-mode') === 'tablet') {
							expandNode.setAttribute('style', 'display: block !important;');
              } else {
							expandNode.removeAttribute('style');
							console.log('Jet Offcanvas: Desktop view, button hidden');
						}
					}

					applyMobileStyles();

						if ( parent ) {
							parent.classList.add( 'jet-offcanvas-parent' );
						}

						if ( offcanv.classList.contains( 'elementor-column' ) ) {
							offcanv.querySelector( '.elementor-element-populated' ).prepend( collapseNode );
						} else {
							offcanv.prepend( collapseNode );
						}

						parent.prepend( expandNode );

						console.log('Jet Offcanvas: Button created for element', offcanv.dataset.id);

						expandNode.firstElementChild.addEventListener( 'click', () => {
							offcanv.classList.add( 'is-active' );
							parent.classList.add( 'is-active' );
							document.body.style.overflow = 'hidden';
							expandNode.style.display = 'none';
						} );

						collapseNode.firstElementChild.addEventListener( 'click', () => {
							offcanv.classList.remove( 'is-active' );
							parent.classList.remove( 'is-active' );
							document.body.style.overflow = '';
							expandNode.style.display = 'block';
						} );

						document.addEventListener( 'keydown', ( event ) => {
							if ( event.key === 'Escape' && offcanv.classList.contains( 'is-active' ) ) {
								offcanv.classList.remove( 'is-active' );
								parent.classList.remove( 'is-active' );
								document.body.style.overflow = '';
								expandNode.style.display = 'block';
							}
						} );

						document.addEventListener( 'click', ( event ) => {

							if ( event.target.classList.contains( 'offcanvas-collapse' ) ) {
								offcanv.classList.remove( 'is-active' );
								parent.classList.remove( 'is-active' );
								expandNode.style.display = 'block';
								return;
							}

							if ( 'BUTTON' === event.target.tagName ) {
								let parentTarget = event.target.closest( '.offcanvas-collapse' );
								if ( parentTarget ) {
									offcanv.classList.remove( 'is-active' );
									parent.classList.remove( 'is-active' );
									expandNode.style.display = 'block';
									return;
								}
							}

							if ( event.target.classList.contains( 'offcanvas-expand' ) ) {
								offcanv.classList.add( 'is-active' );
								parent.classList.add( 'is-active' );
								expandNode.style.display = 'none';
								return;
							}

							if ( 'BUTTON' === event.target.tagName ) {
								let parentTarget = event.target.closest( '.offcanvas-expand' );
								if ( parentTarget ) {
									offcanv.classList.add( 'is-active' );
									parent.classList.add( 'is-active' );
									expandNode.style.display = 'none';
									return;
								}
							}
						} );
			});
		} catch(error) {
			console.error('Jet Offcanvas Error:', error);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initOffcanvas);
	} else {
		initOffcanvas();
	}

	if (typeof window.elementorFrontend !== 'undefined') {
		window.addEventListener('elementor/frontend/init', initOffcanvas);
	}
})();
</script>
<?php
	}

	public function add_styles() {
		?>
		<style>

			.jet-offcanvas-trigger {
				display: inline-flex;
				justify-content: flex-start;
				align-items: center;
				gap: 10px;
				cursor: pointer;
			}

			.jet-offcanvas-icon {
				line-height: 1em;
			}

			.jet-offcanvas-trigger svg {
				width: 1em;
				height: 1em;
				display: block;
			}

			.jet-offcanvas-trigger path {
				fill: currentColor;
			}

			.jet-offcanvas-trigger-wrap {
				display: none;
			}

			/* Mobile devices - actual frontend with media query */
			@media (max-width: 767px) {

				/* Force hide offcanvas by default on mobile */
				.jet-offcanvas {
					position: fixed !important;
					left: -100vw !important;
					top: 0 !important;
					max-width: 90vw !important;
					width: 90vw !important;
					bottom: 0 !important;
					display: block !important;
					z-index: 99999 !important;
					background: #fff !important;
					overflow: auto !important;
					transition: left 300ms ease-in-out !important;
				}

				.jet-offcanvas.is-active {
					left: 0 !important;
				}

				/* The expand button wrapper - created by JS with inline styles */
				.jet-offcanvas-expand-wrap {
					/* Inline styles will handle visibility */
				}

				/* Hide expand button when active */
				.jet-offcanvas-parent.is-active > .jet-offcanvas-expand-wrap {
					display: none !important;
				}

				/* The collapse button inside offcanvas */
				.jet-offcanvas-collapse-wrap {
					display: block !important;
					padding: 15px !important;
					background: #f5f5f5 !important;
					border-bottom: 1px solid #ddd !important;
				}

				.jet-offcanvas-parent.is-active:before {
					content: '';
					position: fixed;
					left: 0;
					top: 0;
					right: 0;
					bottom: 0;
					z-index: 99998;
					background: rgba(0, 0, 0, .8);
					opacity: 1;
					transition: opacity 300ms ease-in-out;
				}

				body.admin-bar .jet-offcanvas-parent > .jet-offcanvas-trigger-wrap {
					top: 66px !important;
				}

				body.admin-bar .jet-offcanvas > .jet-offcanvas-trigger-wrap,
				body.admin-bar .elementor-element-populated > .jet-offcanvas-trigger-wrap {
					margin-top: 46px;
				}
			}

			/* Elementor editor preview mode */
			body[data-elementor-device-mode="mobile"] .jet-offcanvas,
			body[data-elementor-device-mode="tablet"] .jet-offcanvas {
				position: fixed !important;
				left: -100vw !important;
				top: 0 !important;
				max-width: 90vw !important;
				width: 90vw !important;
				bottom: 0 !important;
				display: block !important;
				z-index: 99999 !important;
				background: #fff !important;
				overflow: auto !important;
				transition: left 300ms ease-in-out !important;
			}

			body[data-elementor-device-mode="mobile"] .jet-offcanvas.is-active,
			body[data-elementor-device-mode="tablet"] .jet-offcanvas.is-active {
				left: 0 !important;
			}

			/* Hide expand button when active in editor */
			body[data-elementor-device-mode="mobile"] .jet-offcanvas-parent.is-active > .jet-offcanvas-expand-wrap,
			body[data-elementor-device-mode="tablet"] .jet-offcanvas-parent.is-active > .jet-offcanvas-expand-wrap {
				display: none !important;
			}

			/* Collapse button style in editor */
			body[data-elementor-device-mode="mobile"] .jet-offcanvas-collapse-wrap,
			body[data-elementor-device-mode="tablet"] .jet-offcanvas-collapse-wrap {
				display: block !important;
				padding: 15px !important;
				background: #f5f5f5 !important;
				border-bottom: 1px solid #ddd !important;
			}

			body[data-elementor-device-mode="mobile"] .jet-offcanvas-parent.is-active:before,
			body[data-elementor-device-mode="tablet"] .jet-offcanvas-parent.is-active:before {
				content: '';
				position: fixed;
				left: 0;
				top: 0;
				right: 0;
				bottom: 0;
				z-index: 99998;
				background: rgba(0, 0, 0, .8);
				opacity: 1;
				transition: opacity 300ms ease-in-out;
			}

			body[data-elementor-device-mode="mobile"].admin-bar .jet-offcanvas-parent > .jet-offcanvas-trigger-wrap {
				top: 66px !important;
			}

			body[data-elementor-device-mode="mobile"].admin-bar .jet-offcanvas > .jet-offcanvas-trigger-wrap,
			body[data-elementor-device-mode="mobile"].admin-bar .elementor-element-populated > .jet-offcanvas-trigger-wrap {
				margin-top: 46px;
			}
		</style>
		<?php
	}

	/**
	 * Add offcanvas-related settings
	 */
	public function add_settings( $element, $section_id ) {

		$element->start_controls_section(
			'jet_offcanvas',
			array(
				'tab' => Controls_Manager::TAB_ADVANCED,
				'label' => __( 'Offcanvas Settings', 'jet-offcanvas-column' ),
			)
		);

		$element->add_control(
			'jet_offcanvas_enabled',
			array(
				'type'           => Controls_Manager::SWITCHER,
				'label'          => __( 'Enable', 'jet-offcanvas-column' ),
				'render_type'    => 'template',
				'prefix_class'   => 'jet-offcanvas--',
				'style_transfer' => false,
			)
		);

		$element->add_control(
			'jet_offcanvas_width',
			[
				'label' => esc_html__( 'Offcanvas Panel Width in VW', 'jet-offcanvas-column' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'vw' ],
				'range' => [
					'vw' => [
						'min' => 50,
						'max' => 100,
					],
				],
				'default' => [
					'unit' => 'vw',
					'size' => 90,
				],
				'selectors' => [
					'body[data-elementor-device-mode="mobile"] .jet-offcanvas.elementor-element-{{ID}}' => 'width: {{SIZE}}{{UNIT}} !important;max-width: {{SIZE}}{{UNIT}} !important;',
					'body[data-elementor-device-mode="tablet"] .jet-offcanvas.elementor-element-{{ID}}' => 'width: {{SIZE}}{{UNIT}} !important;max-width: {{SIZE}}{{UNIT}} !important;',
					'@media (max-width: 767px)' => [
						'.jet-offcanvas.elementor-element-{{ID}}' => 'width: {{SIZE}}{{UNIT}} !important;max-width: {{SIZE}}{{UNIT}} !important;',
					],
				],
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_control(
			'jet_offcanvas_expand_text',
			array(
				'type'        => Controls_Manager::TEXT,
				'label'       => __( 'Expand Label', 'jet-offcanvas-column' ),
				'label_block' => true,
				'default'     => 'Expand',
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
				'style_transfer' => false,
			)
		);

		$element->add_control(
			'jet_offcanvas_collapse_text',
			array(
				'type'        => Controls_Manager::TEXT,
				'label'       => __( 'Collapse Label', 'jet-offcanvas-column' ),
				'label_block' => true,
				'default'     => 'Collapse',
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
				'style_transfer' => false,
			)
		);

		$element->add_control(
			'jet_offcanvas_expand_bg_color',
			[
				'label' => __( 'Expand Background Color', 'jet-offcanvas-column' ),
				'separator' => 'before',
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'.jet-offcanvas-{{ID}} .jet-offcanvas-expand' => 'background-color: {{VALUE}}',
				],
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_control(
			'jet_offcanvas_expand_text_color',
			[
				'label' => __( 'Expand Text Color', 'jet-offcanvas-column' ),
				'separator' => 'before',
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'.jet-offcanvas-{{ID}} .jet-offcanvas-expand' => 'color: {{VALUE}}',
				],
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'jet_offcanvas_expand_typography',
				'selector' => '.jet-offcanvas-{{ID}} .jet-offcanvas-expand',
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_control(
			'jet_offcanvas_expand_padding',
			[
				'label' => __( 'Expand Padding', 'jet-offcanvas-column' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'.jet-offcanvas-{{ID}} .jet-offcanvas-expand' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_control(
			'jet_offcanvas_expand_margin',
			[
				'label' => __( 'Expand Margin', 'jet-offcanvas-column' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'.jet-offcanvas-{{ID}} .jet-offcanvas-expand' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'jet_offcanvas_expand_border',
				'label' => __( 'Border', 'jet-offcanvas-column' ),
				'selector' => '.jet-offcanvas-{{ID}} .jet-offcanvas-expand',
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_control(
			'jet_offcanvas_expand_border_radius',
			[
				'label' => __( 'Expand Border Radius', 'jet-offcanvas-column' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'.jet-offcanvas-{{ID}} .jet-offcanvas-expand' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_control(
			'jet_offcanvas_expand_icon',
			[
				'label' => __( 'Expand Button Icon', 'jet-offcanvas-column' ),
				'type' => Controls_Manager::ICONS,
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_control(
			'jet_offcanvas_expand_icon_size',
			[
				'label' => esc_html__( 'Icon Size', 'jet-offcanvas-column' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'range' => [
					'px' => [
						'min' => 6,
						'max' => 300,
					],
				],
				'selectors' => [
					'.jet-offcanvas-{{ID}} .jet-offcanvas-expand .jet-offcanvas-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
					'jet_offcanvas_expand_icon!' => '',
				),
			]
		);

		$element->add_control(
			'jet_offcanvas_collapse_bg_color',
			[
				'label' => __( 'Collapse Background Color', 'jet-offcanvas-column' ),
				'separator' => 'before',
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .jet-offcanvas-collapse' => 'background-color: {{VALUE}}',
				],
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_control(
			'jet_offcanvas_collapse_text_color',
			[
				'label' => __( 'Collapse Text Color', 'jet-offcanvas-column' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .jet-offcanvas-collapse' => 'color: {{VALUE}}',
				],
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'jet_offcanvas_collapse_typography',
				'selector' => '{{WRAPPER}} .jet-offcanvas-collapse',
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_control(
			'jet_offcanvas_collapse_padding',
			[
				'label' => __( 'Collapse Padding', 'jet-offcanvas-column' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .jet-offcanvas-collapse' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_control(
			'jet_offcanvas_collapse_margin',
			[
				'label' => __( 'Collapse Margin', 'jet-offcanvas-column' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .jet-offcanvas-collapse' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'jet_offcanvas_collapse_border',
				'label' => __( 'Collpase Border', 'jet-offcanvas-column' ),
				'selector' => '{{WRAPPER}} .jet-offcanvas-collapse',
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_control(
			'jet_offcanvas_collapse_border_radius',
			[
				'label' => __( 'Collapse Border Radius', 'jet-offcanvas-column' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .jet-offcanvas-collapse' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_control(
			'jet_offcanvas_collapse_icon',
			[
				'label' => __( 'Collapse Button Icon', 'jet-offcanvas-column' ),
				'type' => Controls_Manager::ICONS,
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
				),
			]
		);

		$element->add_control(
			'jet_offcanvas_collapse_icon_size',
			[
				'label' => esc_html__( 'Icon Size', 'jet-offcanvas-column' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'range' => [
					'px' => [
						'min' => 6,
						'max' => 300,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .jet-offcanvas-collapse .jet-offcanvas-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'condition'  => array(
					'jet_offcanvas_enabled' => 'yes',
					'jet_offcanvas_collapse_icon!' => '',
				),
			]
		);

		$element->end_controls_section();

	}

}
