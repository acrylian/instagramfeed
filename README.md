**NOTE: THis plugin is not developed anymore because Instagram basically disabled the feed it used**

# instagramfeed
A [ZenphotoCMS](http://www.zenphoto.org) plugin to display the latest images from a instagram account. 

It does not use the API and does not require any login or tokens. It only works with public content.

**Note: This plugin uses as more or less unofficial way to get the data and does not always work reliable. It was once made as a quick solution without getting into the hassles of the official API. There is no intention to rework this plugin but contributions are welcome**

## Installation

Place the file `instagramfeed.php` to your `/plugins` folder, enable it and set the plugin options. 

Add `instagramFeed::printFeed(4);` to your theme where you want to display the images.

Note the plugin does just print an unordered list with linked thumbs and does not provide any default CSS styling. 

## Customize the display
 
To customize the feed output create child class within your theme's function.php or a custom pugin like this:

    class myInstagramFeed extends instagramFeed {

      static function printFeed($number = 4, $size = 1, $class = 'instagramfeed') {
        $content = instagramFeed::getFeed();
        if ($content) {
          // add your customized output here
        }
      }

    }
