<?php

function clean_url($url) {
    // $parts = parse_url($url);
    // return $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
    // return substr($url, 0, strrpos( $url, '/'));
    // return preg_replace('/^.*>\s*/', '', $url);
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

    // Add YAML bard info
    $markdown .= "long_form:\n";
    foreach ($textArray as $paragraph) {

        if (stristr($paragraph, 'img') !== FALSE) {

            // We are an image
            $links = array();
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

        } else {

            // We are text
            $markdown .= "  -\n";
            $markdown .= "    type: text\n";
            $markdown .= "    text: '" . $paragraph . "</p>'\n";
            print '<br>&bull; Text';

        }

    }

    print '<hr>';

    $markdown .= "---\n";





    // print "<br>";
    // preg_match_all('/<img[^>]+>/i', $text, $result);
    // var_dump(count($result[0]));
    // print "<br>";
    //
    // $resultCount = count($result[0]);
    // $links = array();
    //
    // for ($i = 0; $i < $resultCount; $i++) {
    //     $link = $result[0][$i];
    //     preg_match('[src="(.*?)"]', $link, $matches);
    //     $links[] = $matches[0];
    // }
    // $array = explode(',', $matches[0]);
    // echo '<pre>';
    // $new_str = str_replace(['src=', '"'], '', $links);
    // $new_str = str_replace('"', '', $links);
    // print_r($new_str);
    // echo '</pre>';

    // print_r($links);

    // print '<ul>';
    // foreach ($new_str as $imgsrc) {
        // $markdown .= "\n";
        // $markdown .= "  -\n";
        // $markdown .= "    type: image\n";
        // $markdown .= "    image: $imgsrc\n";
    // }
    // print '</ul>';
    //
    // if (strpos($text, '<img') !== false) {
    //     // print('<br>Images true<br>');
    //     // preg_match("/<ymedia>(.*?)<\/ymedia>/", $Input, $Matches);
    //     // preg_match('[src="(.*?)"]', $text, $imgmatch);
    //     // print_r('Img src: ' . $imgmatch[1]);
    //     // $array = explode(',', $imgmatch[1]);
    //     // print_r($array);
    //
    // }
    //
    // if (strpos($text, 'gallery') !== false) {
    //     // print('<br>Gallery true<br>');
    //     // preg_match("/<ymedia>(.*?)<\/ymedia>/", $Input, $Matches);
    //     preg_match('[ids="(.*?)"]', $text, $matches);
    //     $array = explode(',', $matches[1]);
    //     // print_r($array);
    //
    //     print '<ul>';
    //     foreach ($array as $img) {
    //         // echo '<pre>'; var_dump($img);
    //         // print $img;
    //
    //         foreach ($imgxml->channel->item as $imgitem) {
    //             // print $imgitem->title;
    //
    //             if ($imgitem->wp_post_id == $img) {
    //                 // print '<li>';
    //                 // print $imgitem->guid;
    //                 // print '</li>';
    //
    //                 // $markdown .= "\n";
    //                 // $markdown .= "  -\n";
    //                 // $markdown .= "    type: image\n";
    //                 // $markdown .= "    image: $imgitem->guid\n";
    //
    //             }
    //
    //         }
    //     }
    //     print '</ul>';
    // }
    //

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
