<?php

require("include/header.php");
require("include/db.php");
require("include/rss_util.php");

require_once('php/autoloader.php');

date_default_timezone_set('America/Denver');

// Get feeds
$query = "SELECT * FROM feeds";
$rows = Query($db, $query);

$deleteItems = "DELETE FROM items WHERE 1=1";
Query($db, $deleteItems);

// Load the items for each feed
foreach ($rows as $feed) {
	// Load items for all feeds
	echo "<div><b>Feed id " . $feed['id'] . " link: ";
	echo $feed['link'] . "</b></div>\n";

	$content = new SimplePie();
	$content->set_feed_url($feed['link']);
	$content->enable_order_by_date(false);
	$content->set_cache_location($_SERVER['DOCUMENT_ROOT'] . '/cache');
	$content->init();

	echo "<div>";
	echo $content->get_title();
	echo "</div>\n";
	
	// Display each RSS item
	foreach ($content->get_items() as $item) {
		echo "<div><b>";
		echo $item->get_title();
		echo "</b></div>";

		$imgUrl = Null;

		$enclosure = $item->get_enclosure();
		$imgUrl = $enclosure->get_link();

		if (count($imgUrl) != 0){
			echo "<div>";	
			echo "<a href=".$imgUrl.">";
			echo "<img src=".$imgUrl.">";
			echo "</a>";
			echo "<div>";
		}
		
		echo "<div>";
		echo $item->get_local_date();
		echo "</div>";

		echo "<div>";
		echo $item->get_description();
		echo "</div>";

		// Insert the item in the items table
		$insertString = '';
		if ($item->get_title() == NULL) {
			$insertString = makeStr($feed, $item, 'insert', $imgUrl); 
		} else {
			$insertString = makeStr($feed, $item, 'insert', $imgUrl);
		}
		echo "insertquery=\"" . $insertString . "\"\n";
		Query($db, $insertString);
			
	}
}

function makeStr($feed, $item, $type, $imgUrl){
	$isTitle = 1;
	if ($item->get_title() == NULL){
		$isTitle == NULL;
	}
	if ($isTitle == NULL){
		$query = "INSERT INTO items (id,feedTitle,feedLink,itemPubDate,itemLink,itemDesc,itemImg) VALUES ('";
	} else{
		$query = "INSERT INTO items (id,feedTitle,feedLink,itemTitle,itemPubDate,itemLink,itemDesc,itemImg) VALUES ('";
	}
	$query = $query . 
		$feed['id'] . "','" .	
		$item->get_feed()->get_title() .
		"','" .
		$item->get_feed()->get_permalink() .
		"','";

		if ($isTitle != NULL){
			$query = $query . 
			$item->get_title() .
			"','";
		}
		
		$query = $query . 
		$item->get_local_date() .
		"','" .
		$item->get_permalink() .
		"','" .
		RemoveLinks($item->get_description()) .
		"','" .
		$imgUrl .
		"')";
	return $query;
}


require("include/footer.php");
