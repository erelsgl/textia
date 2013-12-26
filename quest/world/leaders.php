<?php
/** קידוד אחיד
 * @file leaders.php - calculate and display the champions ('leaders') in the World of Textia
 * @author Erel Segal http://tora.us.fm
 * @date 2009-07-31
 * @copyright GPL 
 */
require_once('game.php');

/**
 * @param string $domain 'cities', 'soldiers', 'virtues', etc.
 * @param string $land if given, show news of the given land only.
 */
function show_leaders_for_domain($domain, $land=NULL) {
	$world = "טקסטיה";
	$world_encoded = htmlspecialchars($world);

	$land_condition = ($land? "land=".quote_all($land): "1");
	list($table, $group_function) = table_and_group_function_for_domain($domain);
	if (!$table) return NULL;

	$rows = sql_query_or_die("
		SELECT users.*, $group_function AS count 
		FROM users
		INNER JOIN $table ON(users.id=$table.userid)
		WHERE $land_condition
		GROUP BY userid
		ORDER BY count DESC
		");

	$num_rows = sql_num_rows($rows);

	$domain_text = static_text($domain);

	$html = "
		<table>
			<col class='adventurer' />
			<col class='count' />
		<tbody>
		";
	$html .= "<tr><th>שחקן</th><th>$domain_text</th></tr>\n";
	while ($row = sql_fetch_assoc($rows)) {
		$html .= "<tr>";
		$link_to_user = user_image_with_link($row);
		$count_string = $row['count'];
		$html .= "
		<td>
		$link_to_user
		</td>
		<td>
		$count_string
		</td>
		";
		$html .= "</tr>\n";
	}
	sql_free_result($rows);
	$html .= "</tbody></table>
		<p class='table_total'>".static_text('player count',NULL,$num_rows)."</p>
		";
	echo "
	<h1>".static_text( ($land? 'ranking in land': 'ranking') ,NULL,$domain_text, $land )."</h1>
	$html
	";
}

if (basename(__FILE__)==basename($_SERVER['PHP_SELF'])) {
	show_html_header('leaders');
	require_once('world.php');
	$world = secnodary_object_from_get_or_session('World');
	print "<a class='back' href='world.php'>".($world->title_for_display? $world->title_for_display: 'העולם')."</a>";

	if (!empty($_GET['domain'])) {
		if (isset($_GET['land'])) {
			require_once('land.php');
			$land = secnodary_object_from_get_or_session('Land');
			print "<div class='land'>";
			print "<a class='back' href='land.php'>".$land->title_for_display."</a>";
		} else {
			$land = NULL;
		}
		show_leaders_for_domain($_GET['domain'], $land? $land->title_for_display: NULL);
	}

	show_html_footer(/*$switch_view=*/false);
}

?>