<?php
/**
 * Dentonet/WP/Woo_Stock_Manager/Stock_Options class
 *
 * @package dentonet
 */

namespace Dentonet\WP\Woo_Stock_Manager;

use Dentonet\WP\Woo_Stock_Manager\Component_Interface;
use WC_Product;

/**
 * Class responsible for adding options in WC product panel.
 */
class Stock_Options implements Component_Interface {
	/**
	 * Add all hooks and filters to integrate with WordPress.
	 */
	public function initialize() {
		add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'action_add_inventory_option' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'action_save_synced_products' ) );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'action_update_synced_products_stock_quantity' ) );
		add_action( 'woocommerce_product_set_stock', array( $this, 'action_update_synced_products_stock_quantity' ) );
	}

	/**
	 * Add selecting synced products to inventory tab.
	 */
	public function action_add_inventory_option() {
		global $product_object, $post;
		?>
		<p class="form-field">
	  <label for="synced_products_ids"><?php esc_html_e( 'Synced products', 'woo-stock-manager' ); ?></label>
	  <select class="wc-product-search" multiple="multiple" style="width: 50%;" id="synced_products_ids" name="_synced_products_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-exclude="<?php echo intval( $post->ID ); ?>">
			<?php
			$product_ids = $product_object->get_meta( '_synced_products_ids' );
			foreach ( $product_ids as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( is_object( $product ) ) {
					echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
				}
			}
			?>
	  </select>
	  </p>
		<?php
	}

	/**
	 * Store synced products ids in database.
	 *
	 * @param WC_Product $product Currently processed product object.
	 */
	public function action_save_synced_products( WC_Product $product ) {
		$synced_products_ids = isset( $_POST['_synced_products_ids'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['_synced_products_ids'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$updated_product_id = $product->get_ID();

		$sync_ids = $synced_products_ids;
		$product->update_meta_data( '_synced_products_ids', $sync_ids );

		// Finally connect all items recursively.
		foreach ( $synced_products_ids as $synced_id ) {
			$items_to_connect = $synced_products_ids;
			$items_to_connect[] = $updated_product_id;
			$items_to_connect = array_filter(
				$items_to_connect,
				function ( $item ) use ( $synced_id ) {
					return $item !== $synced_id;
				}
			);
			update_post_meta( $synced_id, '_synced_products_ids', $items_to_connect );
		}
	}

	/**
	 * Update stock status for each connected product when stock update is triggered.
	 * Simply set stock quantity to the same number.
	 *
	 * @param  WC_Product $product Product soon after stock reduce.
	 */
	public function action_update_synced_products_stock_quantity( WC_Product $product ) {
		$synced_products_ids = $product->get_meta( '_synced_products_ids' );
		$stock_quantity = $product->get_stock_quantity();

		foreach ( $synced_products_ids as $synced_id ) {
			$synced_product = wc_get_product( $synced_id );
			$synced_product->set_stock_quantity( $stock_quantity );
			$synced_product->save();
		}
	}

}
