<?php
/*
Plugin Name: JS Redirect
Plugin URI: https://wordpress.org/plugins/js-redirect
Version: 1.0.7
Description: Add JavaScript redirects from one page to another
Author: James Low
Author URI: http://jameslow.com
*/

class JSRedirect {
	static $JS_REDIRECT = 'js_redirect';
	
	public static function addHooks() {
		add_action('add_meta_boxes', array('JSRedirect', 'add_meta_box'));
		add_action('save_post', array('JSRedirect', 'save'));
		add_action('wp_head', array('JSRedirect', 'wp_head'));
	}
	
	public static function wp_head() {
		global $post;
		$redirect = get_post_meta($post->ID, '_'.self::$JS_REDIRECT, true);
		if ($redirect) {
			?>
<script>
function js_redirect_params(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}
(function() {
	var params = ['utm_source','utm_campaign','utm_content'];
	var utm_source = js_redirect_params('utm_source');
	var redirect = '<?php echo get_permalink($redirect); ?>';
	for (var i in params) {
		var key = params[i];
		var value = js_redirect_params(key);
		if (value) {
			redirect += (redirect.indexOf('?')>=0?'&':'?') + key + '=' + encodeURIComponent(value);
		}
	}
	location.href = redirect;
})();
</script>
<?php
		}
	}
	
	public static function add_meta_box($post_type) {
		//if ($post_type == 'post' || $post_type == 'page') {
		if ($post_type == 'page') {
			add_meta_box(
				self::$JS_REDIRECT,
				__('JS Redirect', self::$JS_REDIRECT),
				array('JSRedirect', 'render_meta_box_content'),
				$post_type,
				'advanced',
				'high'
			);
		}
	}
	
	public static function save($post_id) {
		// Check if our nonce is set.
		$nonce = $_POST['js_redirect_nounce'];
		if (!isset($nonce) || !wp_verify_nonce($nonce, self::$JS_REDIRECT) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
			//$_POST['post_type'] != 'post' || $_POST['post_type'] != 'page') {
			$_POST['post_type'] != 'page') {
			return $post_id;
		}
		update_post_meta($post_id, '_'.self::$JS_REDIRECT, $_POST[self::$JS_REDIRECT]);
	}
	
	public static function render_meta_box_content($post) {
		// Add an nonce field so we can check for it later.
		wp_nonce_field(self::$JS_REDIRECT, self::$JS_REDIRECT.'_nounce');
		$redirect = get_post_meta($post->ID, '_'.self::$JS_REDIRECT, true);
		echo '<div id="'.self::$JS_REDIRECT.'">
			<select id="'.self::$JS_REDIRECT.'" name="'.self::$JS_REDIRECT.'">';
		echo '<option value="0">(No Redirect)</option>';
		$posts = get_pages();
		foreach ($posts as $post) {
			echo '<option value="'.$post->ID.'"'.($post->ID == $redirect?' selected="selected"':'').'>'.esc_html($post->post_title).'</option>';
		}
		echo'	</select>
		</div>';
	}
}
JSRedirect::addHooks();