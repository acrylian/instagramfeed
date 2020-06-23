<?php
/**
 * A simple ZenphotoCMS plugin to display the latest public images from an Instagram account
 * 
 * It does not use the API and does not require any login or tokens. It only works with public content.
 * 
 * ## Installation
 * 
 * Place the file `instagramfeed.php` into your `/plugins` folder, enable it and set the plugin options. 
 * 
 * Add `instragramFeed::printFreed(4);` to your theme where you want to display the images.
 * 
 * Note the plugin does just print an unordered list with linked thumbs and does not provide any default CSS styling. 
 * 
 * ## Customize the display
 * 
 * To customize the feed output create child class within your theme's function.php or a custom pugin like this:
 * 
 *     class myInstagramFeed extends instagramFeed {
 * 	
 * 		   static function printFeed($number = 4, $size = 1, $class = 'instagramfeed') {
 * 					$content = flickrFeed::getFeed();
 * 					if ($content) {
 * 						// add your customized output here
 * 				  }
 * 		   }
 *
 *     }
 * 
 * @author Malte Müller (acrylian)
 * @licence GPL v3 or later
 */
$plugin_description = gettext('A simple plugin to display the latest public images from a Instagram account');
$plugin_author = 'Malte Müller (acrylian)';
$plugin_version = '1.0.1';
$plugin_url = 'https/github.com/acrylian/instagramfeed';
$plugin_category = gettext('Media');
$option_interface = 'instagramFeedOptions';

class instagramFeedOptions {

	function __construct() {
		setOptionDefault('instagramfeed_cachetime', 86400);
	}

	function getOptionsSupported() {
		return array(
				gettext('Instagram user') => array(
						'key' => 'instagramfeed_user',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 1,
						'desc' => gettext('The user name of the Instagram account to fetch')),
				gettext('Cache time') => array(
						'key' => 'instagramfeed_cachetime',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 1,
						'desc' => gettext('The time in seconds the cache is kept until the data is fetched freshly')),
				gettext('Clear cache') => array(
						'key' => 'instagramfeed_cacheclear',
						'type' => OPTION_TYPE_CHECKBOX,
						'order' => 1,
						'desc' => gettext('Check and save options to clear the cache on force.'))
		);
	}

	function handleOptionSave($themename, $themealbum) {
		if (isset($_POST['instagramfeed_cacheclear'])) {
			instagramFeed::saveCache('');
			instagramFeed::saveLastmod();
			setOption('instagramfeed_cacheclear', false);
		}
		return false;
	}

}

/**
 * Class to fetch latest images from a Instagram account JSON feed
 */
class instagramFeed {

	/**
	 * Returns an array with the feed information either freshly or from cache.
	 * 
	 * @return array
	 */
	static function getFeed() {
		$user = trim(getOption('instagramfeed_user'));
		if ($user) {
			$feedurl = 'https://www.instagram.com/' . $user . '/?__a=1';
			$cache = instagramFeed::getCache();
			$lastmod = instagramFeed::getLastMod();
			$cachetime = intval(getOption('instagramfeed_cachetime'));
			if (empty($cache) || (time() - $lastmod) > $cachetime) {
				$options = array(
						CURLOPT_HTTPGET => true,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_FAILONERROR => true,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_AUTOREFERER => true,
						CURLOPT_HEADER => false
				);
				$data = curlRequest($feedurl, $options);
				$content = json_decode($data);
				instagramFeed::saveCache($content);
				instagramFeed::saveLastMod(time());
				return $content;
			} else {
				return $cache;
			}
		}
		return array();
	}

	/**
	 * Prints a list of images from a users instagramFeed
	 * 
	 * @param int $number The number of images to display
	 * @param mixed $size The size to display (0-4 = 150x150, 240x240, 320x320, 480x480, 640x640) or "full" for the full image. Falls back to 0 if nothing is set
	 * @param string $class default "instragramfeed" to use the default styling
	 */
	static function printFeed($number = 4, $size = 1, $class = 'instagramfeed') {
		$content = instagramFeed::getFeed();
		$count = '';
		if ($content) {
			$posts = instagramFeed::getPosts($content);
			?>
			<ul class="<?php echo html_encode($class); ?>">
				<?php
				$count = '';
				foreach ($posts as $post) {
					$count++;
					$posturl = instagramFeed::getPostURL($post);
					$location = instagramFeed::getPostLocation($post);
					$text = instagramFeed::getPostDescription($post);
					$date = instagramFeed::getPostDate($post);
					;
					if ($size == 'full') {
						$img = instagramFeed::getPostFullImage($post);
					} else {
						$thumbs = $post->node->thumbnail_resources;
						$img = instagramFeed::getPostThumb($post, $size);
					}
					?>
					<li>
						<a href="<?php echo html_encode($posturl); ?>" title="<?php echo html_encode($location . $date); ?>" target="_blank" rel="noopener">
							<img src="<?php echo html_encode($img['url']); ?>" alt="<?php echo html_encode($text); ?>" width="<?php echo $img['width']; ?>" height="<?php echo $img['height']; ?>">
						</a>
					</li>
					<?php
					if ($count == $number) {
						break;
					}
				}
				?>
			</ul>
			<?php
		}
	}

	/**
	 * Gets the URL to the instragram account of the user
	 * 
	 * @param type $username
	 * @return string
	 */
	static function getUserURL() {
		$user = getOption('instagramfeed_user');
		return 'https://www.instagram.com/' . $user;
	}

	/**
	 * Returns an array with object of the instramm posts.
	 * @param array $content
	 * @return array
	 */
	static function getPosts($content) {
		if ($content) {
			return $content->graphql->user->edge_owner_to_timeline_media->edges;
		}
		return array();
	}

	/**
	 * Returns the instragram post page using the "shortcode" url from the feeed
	 * @param object $post Post object
	 * @return string
	 */
	static function getPostURL($post) {
		return 'https://www.instagram.com/p/' . $post->node->shortcode;
	}

	/**
	 * Returns the post location 
	 * @param object $post
	 * @return string
	 */
	static function getPostLocation($post) {
		if (!empty($post->node->location->name)) {
			return $post->node->location->name;
		}
	}

	/**
	 * Returns the post description/text
	 * @param object $post
	 * @return string
	 */
	static function getPostDescription($post) {
		return $post->node->edge_media_to_caption->edges[0]->node->text;
	}

	/**
	 * Returns the date formatted following Zenphoto's settings
	 * @param object $post
	 * @return string
	 */
	static function getPostDate($post) {
		return zpFormattedDate(DATE_FORMAT, $post->node->taken_at_timestamp);
	}

	/**
	 * 	Returns an array with "url", "width" and "height" of the full image
	 * @param object $post
	 * @return array
	 */
	static function getPostFullImage($post) {
		return array(
				'url' => $post->node->display_url,
				'width' => $post->node->dimensions->width,
				'height' => $post->node->dimensions->height
		);
	}

	/**
	 * Returns an array with "url", "width" and "height" of the thumb
	 * @param object $post
	 * @param int $size The size to display (0-4 = 150x150, 240x240, 320x320, 480x480, 640x640)
	 * @return array
	 */
	static function getPostThumb($post, $size = 0) {
		$thumbs = $post->node->thumbnail_resources;
		if (!array_key_exists($size, $thumbs)) {
			$size = 0;
		}
		return array(
				'url' => $thumbs[$size]->src,
				'width' => $thumbs[$size]->config_width,
				'height' => $thumbs[$size]->config_height
		);
	}

	/**
	 * Gets the content from cache if available
	 * @return array
	 */
	static function getCache() {
		$cache = query_single_row('SELECT data FROM ' . prefix('plugin_storage') . ' WHERE `type` = "instagramfeed" AND `aux` = "instagramfeed_cache"');
		if ($cache) {
			return json_decode(unserialize($cache['data']));
		}
		return false;
	}

	/**
	 * Stores the content in cache
	 * @param array $content
	 */
	static function saveCache($content) {
		$hascache = instagramfeed::getCache();
		$cache = serialize(json_encode($content));
		if ($hascache) {
			$sql = 'UPDATE ' . prefix('plugin_storage') . ' SET `data`=' . db_quote($cache) . ' WHERE `type`="instagramfeed" AND `aux` = "instagramfeed_cache"';
		} else {
			$sql = 'INSERT INTO ' . prefix('plugin_storage') . ' (`type`,`aux`,`data`) VALUES ("instagramfeed", "instagramfeed_cache",' . db_quote($cache) . ')';
		}
		query($sql);
	}

	/**
	 * Returns the time of the last caching
	 * @return int
	 */
	static function getLastMod() {
		$lastmod = query_single_row('SELECT data FROM ' . prefix('plugin_storage') . ' WHERE `type`="instagramfeed" AND `aux` = "instagramfeed_lastmod"');
		if ($lastmod) {
			return $lastmod['data'];
		}
		return false;
	}

	/**
	 * Sets the last modification time
	 * 
	 * @param int $lastmod Time (time()) of the last caching
	 */
	static function saveLastmod() {
		$haslastmod = instagramfeed::getLastMod();
		$lastmod = time();
		if ($haslastmod) {
			$sql = 'UPDATE ' . prefix('plugin_storage') . ' SET `data` = ' . $lastmod . ' WHERE `type`="instagramfeed" AND `aux` = "instagramfeed_lastmod"';
		} else {
			$sql = 'INSERT INTO ' . prefix('plugin_storage') . ' (`type`,`aux`,`data`) VALUES ("instagramfeed", "instagramfeed_lastmod",' . $lastmod . ')';
		}
		query($sql);
	}

}
