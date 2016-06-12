<?php
/*
Plugin Name: Small Archives
Description: monthly archive widget, display smaller list in the sidebar.
Version: 1.1.1
Plugin URI: https://github.com/matsuoshi/wp-small-archives
Author: matsuoshi
Author URI: https://github.com/matsuoshi/
License: GPLv2
Text Domain: small-archives
Domain Path: /languages
*/

/**
 * Small Archives Widget class
 */
class SmallArchivesWidget extends WP_Widget
{
	public $text_domain = 'small-archives';


	/**
	 * constructor
	 */
	function __construct()
	{
		add_action('get_header', array($this, 'addCss'));
		load_plugin_textdomain($this->text_domain, false, dirname(plugin_basename(__FILE__)) . '/languages');

		parent::__construct(false, 'Small Archives');
	}


	/**
	 * widget main
	 * @param array $args
	 * @param array $instance
	 */
	function widget($args, $instance)
	{
		extract($args);
		/** @var string $before_widget */
		/** @var string $before_title */
		/** @var string $after_title */
		/** @var string $after_widget */

		echo $before_widget;

		$title = (! empty($instance['title'])) ? $instance['title'] : __('Archives', $this->text_domain);
		echo $before_title . apply_filters('widget_title', $title) . $after_title;

		$archives = $this->getArchives();
		if ($archives) {
			if (! empty($instance['reverseYearOrder'])) {
				krsort($archives);
			}
?>
			<ul class="smallArchivesYearList">
			<?php
			foreach ($archives as $year => $archive) :
				if (! empty($instance['reverseMonthOrder'])) {
					krsort($archive);
				}
				$year_url = get_year_link($year);
			?>
				<li>
					<span><a href="<?php echo $year_url ?>"><?php echo $year ?></a></span>
					<ul class="smallArchivesMonthList">
					<?php
					foreach ($archive as $month => $count) :

						$month_after = ($instance['showPostCount']) ? "<span>({$count})</span>" : '';
						$month_link = get_archives_link(get_month_link($year, $month), $month, '', '', $month_after);
					?>
						<li><?php echo $month_link ?></li>
					<?php endforeach; ?>
					</ul>
				</li>
			<?php endforeach; ?>
			</ul>
<?php
		}

		echo $after_widget . "\n";
	}


	/**
	 * call query
	 * @return array
	 */
	function getArchives()
	{
		global $wpdb;

		// create query
		$query = "
			SELECT
				YEAR(post_date) AS `year`,
				MONTH(post_date) AS `month`,
				count(ID) as posts
			FROM
				{$wpdb->posts}
			WHERE
				post_type = 'post' AND
				post_status = 'publish'
			GROUP BY
				YEAR(post_date), MONTH(post_date)
			ORDER BY
				post_date ASC
		";

		// query
		$results = $wpdb->get_results($query);

		// format result
		$archives = array();
		foreach ($results as $result) {
			$archives[$result->year][$result->month] = $result->posts;
		}

		return $archives;
	}


	/**
	 * update widget (admin page)
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$new_instance = wp_parse_args((array)$new_instance, array(
			'title' => '',
			'reverseYearOrder' => false,
			'reverseMonthOrder' => false,
			'showPostCount' => false,
		));

		$instance['title'] = strip_tags($new_instance['title']);
		$instance['reverseYearOrder'] = (boolean)$new_instance['reverseYearOrder'];
		$instance['reverseMonthOrder'] = (boolean)$new_instance['reverseMonthOrder'];
		$instance['showPostCount'] = (boolean)$new_instance['showPostCount'];

		return $instance;
	}


	/**
	 * config widget (admin page)
	 * @param array $instance
	 * @return string|void
	 */
	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, array(
			'title' => '',
			'reverseYearOrder' => true,
			'reverseMonthOrder' => false,
			'showPostCount' => false,
		));

		$title = strip_tags($instance['title']);
		$reverseYearOrder = $instance['reverseYearOrder'] ? 'checked="checked"' : '';
		$reverseMonthOrder = $instance['reverseMonthOrder'] ? 'checked="checked"' : '';
		$showPostCount = $instance['showPostCount'] ? 'checked="checked"' : '';
?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">
				<?php _e('Title:', $this->text_domain); ?>
			</label>
			<input type="text" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>" />
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php echo $reverseYearOrder; ?> id="<?php echo $this->get_field_id('reverseYearOrder'); ?>" name="<?php echo $this->get_field_name('reverseYearOrder'); ?>" /> <label for="<?php echo $this->get_field_id('reverseYearOrder'); ?>"><?php _e('Reverse year order', $this->text_domain); ?></label>
			<br/>
			<input class="checkbox" type="checkbox" <?php echo $reverseMonthOrder; ?> id="<?php echo $this->get_field_id('reverseMonthOrder'); ?>" name="<?php echo $this->get_field_name('reverseMonthOrder'); ?>" /> <label for="<?php echo $this->get_field_id('reverseMonthOrder'); ?>"><?php _e('Reverse month order', $this->text_domain); ?></label>
			<br/>
			<input class="checkbox" type="checkbox" <?php echo $showPostCount; ?> id="<?php echo $this->get_field_id('showPostCount'); ?>" name="<?php echo $this->get_field_name('showPostCount'); ?>" /> <label for="<?php echo $this->get_field_id('showPostCount'); ?>"><?php _e('Show post counts', $this->text_domain); ?></label>
		</p>
<?php
	}


	/**
	 * add css file
	 */
	function addCss()
	{
		wp_enqueue_style('SmallArchives', plugins_url('small-archives.css', __FILE__), false);
	}
}


add_action('widgets_init', create_function('', 'return register_widget("SmallArchivesWidget");'));
