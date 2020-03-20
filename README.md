# instagramfeed
A [ZenphotoCMS](http://www.zenphoto.org) plugin to display the latest images from a instagram account. 

It does not use the API and does not require any login or tokens. It only works with public content.

## Installation

Place the file `instagramfeed.php` into your `/plugins` folder, enable it and set the plugin options. 

Add `instagramFeed::printFeed(4);` to your theme where you want to display the images.

Note the plugin does just print an unordered list with linked thumbs and does not provide any default CSS styling. 

## Customize the display
 
To customize the feed output create child class within your theme's function.php or a custom pugin like this:

    class myInstagramFeed extends instagramFeed {

      static function printFeed($number = 4, $size = 1, $class = 'instagramfeed') {
        $content = flickrFeed::getFeed();
        if ($content) {
          // add your customized output here
        }
      }

    }
