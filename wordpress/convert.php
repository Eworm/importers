<?php

function clean_url($url) {
    return '/assets/' . basename($url);
}

// Edit the details below to your neds
$wp_file        = 'data/wp.xml';
$img_file       = 'data/img.xml';
$export_folder  = 'content/'; // existing files will be over-written use with care

if (file_exists($wp_file)) {

    print '<style>* {margin: 0; padding: 0; font-family: arial;}</style>';
    print '<style>body {padding: 32px;}</style>';
    print '<style>hr { margin: 32px 0; border: 1px solid #dedede; border-top: 0; }</style>';
    print '<style>h1, a { margin: 0 0 8px; display: block; }</style>';
    print '<style>h1 { font-size: 24px; }</style>';

  $xml = simplexml_load_file($wp_file);
  $imgxml = simplexml_load_file($img_file);

  $count = 0;
  foreach ($xml->channel->item as $item) {
    $count ++;

    print "<h1>" . $item->title . "</h1>";

    // Simple article info
    $title      = $item->title;
    $item_date  = strtotime($item->pubDate);
    $file_name  = $export_folder.date("Y-m-d", $item_date) . "." . slugify($title) . ".md";
    $text       = strip_tags($item->children('content', true)->encoded, '<br><iframe><a><img>');

    // Convert line breaks to paragraphs
    $text       = preg_replace('/[^\r\n]+/', "<p>$0</p>", $text);

    // Remove whitespace
    $text       = preg_replace( "/\r|\n/", "", $text);

    // Remove empty paragraphs
    $text       = str_replace( "<p>&nbsp;</p>", "", $text);
    $text       = str_replace( ' target="_blank"', "", $text);

    // Make array from all paragraphs
    $textArray  = explode('</p>', $text);

    // If there's no title
    if ($title  == '') {
        $title  = 'untitled post';
    }

    // Print the filename
    print '<a href="#">' . $file_name . '</a>';

    // Add YAML title
    $markdown  = "---\n";
    $markdown .= "title: '" . $title . "'\n";

    // Add intro_image bases on the thumbnail id
    foreach ($item->wp_postmeta as $meta) {

        if ($meta->wp_meta_key == '_thumbnail_id') {
            $featuredId = $meta->wp_meta_value;
            $featuredIdS = explode(',', $featuredId[0]);

            foreach ($imgxml->channel->item as $imgitem) {

                if ($imgitem->wp_post_id == $featuredIdS[0]) {

                    $url = clean_url($imgitem->guid);
                    print '<br>&bull; Featured image: ' . $url;
                    $markdown .= "intro_image: " . $url . " \n";

                }
            }
        }
    }

    // Add tags
    $markdown .= "tags:\n";
    foreach ($item->category as $category) {

        if ($category[domain] == 'category') {

            $cat = $category[nicename];
            $markdown .= "  - " . $cat . "\n";
            print '<br>&bull; Category: ' . $cat;

        }

    }

    // Add YAML bard info
    $markdown .= "long_form:\n";
    foreach ($textArray as $paragraph) {

        if (stristr($paragraph, 'img') !== FALSE) {

            // We are an image

            // Remove p tags
            $paragraph  = str_replace(['<p>', '</p>'], '', $paragraph);
            // Get src
            preg_match('[src="(.*?)"]', $paragraph, $link);
            // Remove parts of the string
            $paragraph  = str_replace(['src=', '"'], '', $link[0]);

            // Write YAML
            $markdown .= "  -\n";
            $markdown .= "    type: image\n";
            $markdown .= "    image: " . clean_url($paragraph) . "\n";
            print '<br>&bull; Image: ' . clean_url($paragraph);

        } elseif (stristr($paragraph, 'gallery') !== FALSE) {

            // We are a gallery
            preg_match('[ids="(.*?)"]', $paragraph, $matches);
            // Make an array with img id's
            $galleryIds = explode(',', $matches[1]);

            // Write YAML
            $markdown .= "  -\n";
            $markdown .= "    type: gallery\n";
            $markdown .= "    images:\n";

            foreach ($galleryIds as $img) {

                foreach ($imgxml->channel->item as $imgitem) {

                    if ($imgitem->wp_post_id == $img) {
                        // Clean the url
                        $url = clean_url($imgitem->guid);
                        // Match the img id and output the url
                        $markdown .= "      - $url\n";
                        print '<br>&bull; Gallery: ' . clean_url($imgitem->guid);
                    }

                }
            }

        } elseif (stristr($paragraph, 'vimeo') !== FALSE) {

            // We are Vimeo
            print '<br>&bull; Vimeo: ';
            $paragraph  = str_replace(['[embed]', '[/embed]', '<p>', '</p>'], '', $paragraph);
            $paragraph  = preg_replace('/^.*\/\s*/', '', $paragraph);
            print $paragraph;

            // Write YAML
            $markdown .= "  -\n";
            $markdown .= "    type: vimeo\n";
            $markdown .= "    url: " . $paragraph . "\n";

        } elseif (stristr($paragraph, 'iframe') !== FALSE) {

            // We are a YouTube iframe
            print '<br>&bull; Youtube: ';
            // Remove p tags
            $paragraph  = str_replace(['<p>', '</p>'], '', $paragraph);
            // Get src
            preg_match('[src="(.*?)"]', $paragraph, $link);
            // Remove parts of the string
            $paragraph  = str_replace(['src=', '"'], '', $link[0]);
            print $paragraph;

            // Write YAML
            $markdown .= "  -\n";
            $markdown .= "    type: youtube\n";
            $markdown .= "    url: '" . $paragraph . "</p>'\n";

        } else {

            // We are text

            // Write YAML
            $markdown .= "  -\n";
            $markdown .= "    type: text\n";
            $markdown .= "    text: '" . $paragraph . "</p>'\n";
            print '<br>&bull; Text';

        }

    }

    print '<hr>';

    $markdown .= "---\n";

    file_put_contents($file_name, $markdown);

    print "\n";

  }
}

// credit: http://sourcecookbook.com/en/recipes/8/function-to-slugify-strings-in-php
function slugify($text)
{
    // replace non letter or digits by -
    $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

    // trim
    $text = trim($text, '-');

    // transliterate
    if (function_exists('iconv'))
    {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }

    // lowercase
    $text = strtolower($text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    if (empty($text))
    {
        return 'n-a';
    }

    return $text;
}
