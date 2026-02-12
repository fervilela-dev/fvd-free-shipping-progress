<?php
/**
 * Plugin Name: FVD Woo Free Shipping Progress
 * Plugin URI: https://github.com/fervilela-dev/fvd-free-shipping-progress
 * Description: Muestra una barra de progreso que indica cuánto falta para llegar al envío gratuito (WooCommerce).
 * Version: 1.0.3
 * Author: FerVilela Digital Consulting
 * Author URI: https://fervilela.com
 * Text Domain: fvd-free-shipping-progress
 * GitHub Plugin URI: https://github.com/fervilela-dev/fvd-free-shipping-progress
 * Primary Branch: main
 */

defined('ABSPATH') || exit;

if (!class_exists('FVD_Free_Shipping_Progress')) {

final class FVD_Free_Shipping_Progress {
	const OPT_KEY = 'fvd_freeship_settings';
	const NONCE_ACTION = 'fvd_freeship_save_settings';
	const AJAX_ACTION = 'fvd_freeship_fragment';

	public function __construct() {
		add_action('plugins_loaded', [$this, 'boot']);
	}

	public function boot() {
		if (!class_exists('WooCommerce')) {
			add_action('admin_notices', [$this, 'notice_requires_woocommerce']);
			return;
		}

		add_action('init', [$this, 'register_shortcode']);

		// Frontend assets
		add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

		// Display hooks (optional)
		add_action('woocommerce_before_cart', [$this, 'render_on_cart']);
		add_action('woocommerce_before_checkout_form', [$this, 'render_on_checkout'], 9);
		add_action('woocommerce_widget_shopping_cart_before_buttons', [$this, 'render_on_mini_cart'], 9);

		// AJAX + fragments (auto-refresh)
		add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'ajax_fragment']);
		add_action('wp_ajax_nopriv_' . self::AJAX_ACTION, [$this, 'ajax_fragment']);
		add_filter('woocommerce_add_to_cart_fragments', [$this, 'add_fragment']);

		// Xootix Side Cart (xoo-wsc) integration: footer hook lives inside modal.
		add_action('xoo_wsc_footer', [$this, 'render_on_xoo_sidecart']);

		// Admin settings
		add_action('admin_menu', [$this, 'admin_menu']);
		add_action('admin_init', [$this, 'maybe_save_settings']);
		register_activation_hook(__FILE__, [__CLASS__, 'activate']);
	}

	public static function activate() {
		$defaults = [
			'enabled' => 'yes',
			'goal_amount' => '150', // MONTO META (ajústalo en WooCommerce > FVD Free Shipping)
			'include_discounts' => 'no', // si "yes" descuenta cupones/descuentos del progreso
			'show_cart' => 'yes',
			'show_checkout' => 'yes',
			'show_mini_cart' => 'yes',
		];
		if (!get_option(self::OPT_KEY)) {
			add_option(self::OPT_KEY, $defaults, '', false);
		}
	}

	private function get_settings(): array {
		$s = get_option(self::OPT_KEY, []);
		$s = is_array($s) ? $s : [];
		$defaults = [
			'enabled' => 'yes',
			'goal_amount' => '150',
			'include_discounts' => 'no',
			'show_cart' => 'yes',
			'show_checkout' => 'yes',
			'show_mini_cart' => 'yes',
		];
		return array_merge($defaults, $s);
	}

	public function notice_requires_woocommerce() {
		if (!current_user_can('activate_plugins')) return;
		echo '<div class="notice notice-warning"><p><strong>FVD Woo Free Shipping Progress</strong> requiere WooCommerce activo.</p></div>';
	}

	public function register_shortcode() {
		add_shortcode('fvd_free_shipping_bar', [$this, 'shortcode']);
	}

	public function enqueue_assets() {
		$settings = $this->get_settings();
		if (($settings['enabled'] ?? 'yes') !== 'yes') return;

		// Siempre cargamos si se quiere mostrar en mini-cart/side-cart.
		$should_enqueue = ($settings['show_mini_cart'] ?? 'yes') === 'yes';

		// Para el resto de contextos seguimos limitando a páginas relevantes o cuando el shortcode está presente.
		if (!$should_enqueue) {
			if (!is_cart() && !is_checkout() && !is_shop() && !is_product() && !is_product_category() && !is_product_tag()) {
				global $post;
				$has_shortcode = is_object($post) && isset($post->post_content) && has_shortcode($post->post_content, 'fvd_free_shipping_bar');
				if (!$has_shortcode) return;
			}
		}

		wp_register_style(
			'fvd-freeship',
			plugins_url('assets/fvd-freeship.css', __FILE__),
			[],
			'1.0.3'
		);
		wp_enqueue_style('fvd-freeship');

		wp_register_script(
			'fvd-freeship',
			plugins_url('assets/fvd-freeship.js', __FILE__),
			['jquery'],
			'1.0.3',
			true
		);

		wp_localize_script('fvd-freeship', 'FVD_FREESHIP', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'action'  => self::AJAX_ACTION,
			'nonce'   => wp_create_nonce(self::AJAX_ACTION),
		]);

		wp_enqueue_script('fvd-freeship');
	}

	/* -------------------------
	 * RENDERS
	 * ------------------------- */

	public function render_on_cart() {
		$settings = $this->get_settings();
		if (($settings['enabled'] ?? 'yes') !== 'yes') return;
		if (($settings['show_cart'] ?? 'yes') !== 'yes') return;
		echo $this->render_bar();
	}

	public function render_on_checkout() {
		$settings = $this->get_settings();
		if (($settings['enabled'] ?? 'yes') !== 'yes') return;
		if (($settings['show_checkout'] ?? 'yes') !== 'yes') return;
		echo $this->render_bar();
	}

	public function render_on_mini_cart() {
		$settings = $this->get_settings();
		if (($settings['enabled'] ?? 'yes') !== 'yes') return;
		if (($settings['show_mini_cart'] ?? 'yes') !== 'yes') return;
		echo $this->render_bar('mini');
	}

	// Xootix Woo Side Cart (xoo-wsc) modal support: uses its own hook.
	public function render_on_xoo_sidecart() {
		$settings = $this->get_settings();
		if (($settings['enabled'] ?? 'yes') !== 'yes') return;
		if (($settings['show_mini_cart'] ?? 'yes') !== 'yes') return;
		echo '<div class="xoo-wsc-fvd">' . $this->render_bar('mini') . '</div>';
	}

	public function shortcode($atts = []) {
		$settings = $this->get_settings();
		if (($settings['enabled'] ?? 'yes') !== 'yes') return '';
		$atts = shortcode_atts([
			'context' => 'shortcode', // o "mini"
			'goal'    => '',          // opcional: override de monto meta
		], $atts, 'fvd_free_shipping_bar');

		return $this->render_bar(sanitize_text_field($atts['context']), $atts['goal']);
	}

	private function get_goal_amount($override = ''): float {
		$settings = $this->get_settings();
		$goal = $override !== '' ? $override : ($settings['goal_amount'] ?? '0');
		$goal = preg_replace('/[^0-9\.,]/', '', (string)$goal);
		$goal = str_replace(',', '.', $goal);
		return max(0.0, (float)$goal);
	}

	private function get_progress_base_total(): float {
		$settings = $this->get_settings();

		if (!WC()->cart) return 0.0;
		$subtotal = (float) WC()->cart->get_subtotal(); // suma de ítems, sin envío
		$discount = (float) WC()->cart->get_discount_total();

		$include_discounts = ($settings['include_discounts'] ?? 'no') === 'yes';
		if ($include_discounts) {
			$subtotal = max(0.0, $subtotal - $discount);
		}
		return $subtotal;
	}

	private function render_bar(string $context = 'default', $override_goal = ''): string {
		$goal = $this->get_goal_amount((string)$override_goal);

		// Si no hay meta, no renderizamos (evita confusión)
		if ($goal <= 0) return '';

		$current = $this->get_progress_base_total();
		$remaining = max(0.0, $goal - $current);
		$percent = $goal > 0 ? min(100, ($current / $goal) * 100) : 0;

		$message = $remaining > 0
			? sprintf('Te faltan <strong>%s</strong> para obtener <strong>envío gratis</strong>.', wc_price($remaining))
			: '¡Listo! Ya tienes <strong>envío gratis</strong>.';

		ob_start(); ?>
		<div class="fvd-freeship-bar" data-context="<?php echo esc_attr($context); ?>" aria-live="polite">
			<div class="fvd-freeship-text"><?php echo wp_kses_post($message); ?></div>
			<div class="fvd-freeship-track" role="progressbar"
				aria-valuenow="<?php echo esc_attr((int) round($percent)); ?>"
				aria-valuemin="0" aria-valuemax="100">
				<div class="fvd-freeship-fill" style="width: <?php echo esc_attr($percent); ?>%;"></div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/* -------------------------
	 * AJAX + FRAGMENTS
	 * ------------------------- */

	public function ajax_fragment() {
		check_ajax_referer(self::AJAX_ACTION, 'nonce');

		$context = isset($_REQUEST['context']) ? sanitize_text_field(wp_unslash($_REQUEST['context'])) : 'default';
		$goal    = isset($_REQUEST['goal']) ? sanitize_text_field(wp_unslash($_REQUEST['goal'])) : '';

		wp_send_json_success([
			'html' => $this->render_bar($context, $goal),
		]);
	}

	public function add_fragment($fragments) {
		// Recalcula la barra en cada refresh de fragments (add to cart, remove, etc.)
		$fragments['div.fvd-freeship-bar'] = $this->render_bar();
		return $fragments;
	}

	/* -------------------------
	 * ADMIN
	 * ------------------------- */

	public function admin_menu() {
		add_submenu_page(
			'woocommerce',
			'FVD Free Shipping',
			'FVD Free Shipping',
			'manage_woocommerce',
			'fvd-free-shipping',
			[$this, 'settings_page']
		);
	}

	public function maybe_save_settings() {
		if (!is_admin() || !current_user_can('manage_woocommerce')) return;
		if (!isset($_POST['fvd_freeship_save'])) return;

		check_admin_referer(self::NONCE_ACTION);

		$settings = $this->get_settings();
		$settings['enabled'] = isset($_POST['enabled']) ? 'yes' : 'no';
		$settings['goal_amount'] = isset($_POST['goal_amount']) ? sanitize_text_field(wp_unslash($_POST['goal_amount'])) : $settings['goal_amount'];
		$settings['include_discounts'] = isset($_POST['include_discounts']) ? 'yes' : 'no';
		$settings['show_cart'] = isset($_POST['show_cart']) ? 'yes' : 'no';
		$settings['show_checkout'] = isset($_POST['show_checkout']) ? 'yes' : 'no';
		$settings['show_mini_cart'] = isset($_POST['show_mini_cart']) ? 'yes' : 'no';

		update_option(self::OPT_KEY, $settings, false);

		add_action('admin_notices', function() {
			echo '<div class="notice notice-success is-dismissible"><p>Ajustes guardados.</p></div>';
		});
	}

	public function settings_page() {
		if (!current_user_can('manage_woocommerce')) return;
		$s = $this->get_settings();
		?>
		<div class="wrap">
			<h1>FVD Free Shipping</h1>
			<p>Configura el monto meta para mostrar la barra de progreso hacia <strong>envío gratis</strong>.</p>

			<form method="post" action="">
				<?php wp_nonce_field(self::NONCE_ACTION); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Activar</th>
						<td>
							<label><input type="checkbox" name="enabled" <?php checked($s['enabled'], 'yes'); ?> /> Habilitado</label>
						</td>
					</tr>
					<tr>
						<th scope="row">Monto meta (<?php echo esc_html(get_woocommerce_currency()); ?>)</th>
						<td>
							<input type="text" name="goal_amount" value="<?php echo esc_attr($s['goal_amount']); ?>" class="regular-text" />
							<p class="description">Ejemplo: 150 o 199.90</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Contabilizar descuentos/cupones</th>
						<td>
							<label><input type="checkbox" name="include_discounts" <?php checked($s['include_discounts'], 'yes'); ?> /> Sí (resta descuentos al progreso)</label>
						</td>
					</tr>
					<tr>
						<th scope="row">Mostrar automáticamente en</th>
						<td>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="show_cart" <?php checked($s['show_cart'], 'yes'); ?> /> Carrito
							</label>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="show_checkout" <?php checked($s['show_checkout'], 'yes'); ?> /> Checkout
							</label>
							<label style="display:block;">
								<input type="checkbox" name="show_mini_cart" <?php checked($s['show_mini_cart'], 'yes'); ?> /> Mini-cart / Side-cart
							</label>
							<p class="description">También puedes insertarlo donde quieras con el shortcode <code>[fvd_free_shipping_bar]</code> (ideal para Elementor).</p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary" name="fvd_freeship_save" value="1">Guardar cambios</button>
				</p>
			</form>
		</div>
		<?php
	}
}

new FVD_Free_Shipping_Progress();
}
