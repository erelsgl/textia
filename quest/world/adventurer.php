<?php
/** קידוד אחיד
 * @file adventurer.php - calculate and show statistical information about a player in the World of Textia.
 * @author Erel Segal http://tora.us.fm
 * @date 2009-07-10
 * @copyright GPL 
 */
require_once('game.php');

function city_stats($id_quoted) {
	$stats = sql_evaluate_array_key_value("
		SELECT land, count(*)
		FROM user_city
		WHERE userid=$id_quoted
		GROUP BY land
		WITH ROLLUP
		");
	//print_r($stats);
	return $stats;
}

function soldier_stats($id_quoted) {
	$stats = sql_evaluate_array_key_value("
		SELECT land, count(*)
		FROM user_soldier
		WHERE userid=$id_quoted
		GROUP BY land
		WITH ROLLUP
		");
	//print_r($stats);
	return $stats;
}

function soldier_loyalty_stats($id_quoted) {
	$stats = sql_evaluate_array_key_value("
		SELECT land, sum(loyalty)
		FROM user_soldier_loyalty
		WHERE userid=$id_quoted
		GROUP BY land
		WITH ROLLUP
		");
	//print_r($stats);
	return $stats;
}

function show_adventurer_stats($id_quoted) {
	$city_stats = city_stats($id_quoted);
	$soldier_stats = soldier_stats($id_quoted);
	$soldier_loyalty_stats = soldier_loyalty_stats($id_quoted);

	// Find all lands relevant to the adventurer
	$lands = array();
	foreach ($city_stats as $land=>$value) $lands[$land]['cities']=$value;
	foreach ($soldier_stats as $land=>$value) $lands[$land]['soldiers']=$value;
	foreach ($soldier_loyalty_stats as $land=>$value) $lands[$land]['loyalty']=$value;

	print "
		<table class='adventurer_stats'>
			<col class='land' />
			<col class='cities' />
			<col class='soldiers' />
		<thead>
			<tr class='heading'>
				<th>ארץ</th>
				<th>ערים</th>
				<th>חיילים (נאמנות)</th>
			</tr>
		</thead>
		<tbody>
		";

	asort($lands);
	foreach ($lands as $land=>$values) {
		$row_class = ($land? "": "total");
		$land_anchor = ($land? 
			land_anchor($land): 
			static_text('total')
			);
		print "
			<tr class='$row_class'>
				<td>$land_anchor</td>
				<td>".coalesce($values['cities'],0)."</td>
				<td>".coalesce($values['soldiers'],0)." (".coalesce($values['loyalty'],0).")</td>
			</tr>
			";
	}
	print "
		</tbody>
		</table>
		";
}

function show_treasure_stats($id_quoted) {
	$rows = sql_query_or_die("
		SELECT treasure AS name, image, count(*) AS value
		FROM user_treasure
		LEFT JOIN treasure_data ON(user_treasure.treasure=treasure_data.name)
		WHERE userid=$id_quoted
		GROUP BY treasure
		WITH ROLLUP
		");

	if (sql_num_rows($rows)) {
		print "
			<table class='treasure_stats'>
				<col class='treasure_name' />
				<col class='count' />
			<thead>
				<tr class='heading'>
					<th>אוצר</th>
					<th>כמות</th>
				</tr>
			</thead>
			<tbody>
			";

		while ($row = sql_fetch_assoc($rows)) {
			$row_class = ($row['name']? "": "total");
			$treasure_anchor = ($row['name']?
				treasure_image_html($row):
				static_text('total')
				);
			print "
				<tr class='$row_class'>
					<td>$treasure_anchor</td>
					<td>$row[value]</td>
				</tr>
				";
		}
		print "
			</tbody>
			</table>
			";
	} else {
		print "<div><b>אוצרות</b>: עדיין לא מצאת!</div>";
	}
}

function show_virtue_stats($id_quoted) {
	$rows = sql_query_or_die("
		SELECT 
			virtue_count.virtue AS name, count(userid) AS current_value, virtue_count.count AS total_value
		FROM virtue_count 
		LEFT JOIN user_article_virtue ON(userid=$id_quoted AND user_article_virtue.virtue=virtue_count.virtue)
		GROUP BY virtue_count.virtue
		");

	print "
		<table class='treasure_stats'>
			<col class='treasure_name' />
			<col class='count' />
		<thead>
			<tr class='heading'>
				<th>מידה</th>
				<th>דרגה מירבית/נוכחית</th>
			</tr>
		</thead>
		<tbody>
		";

	while ($row = sql_fetch_assoc($rows)) {
		$row_class = ($row['name']? "": "total");
		$treasure_anchor = ($row['name']?
			$row['name']:
			static_text('total')
			);
		print "
			<tr class='$row_class'>
				<td>$treasure_anchor</td>
				<td>$row[current_value]/$row[total_value]</td>
			</tr>
			";
	}
	print "
		</tbody>
		</table>
		";
}

function show_tguvot($gfc_userid) {
	$gfc_userid_quoted = quote_all($gfc_userid);
	$rows = sql_query_or_die("
		SELECT *, ADDDATE(tguvot.created_at, INTERVAL 10 HOUR) AS created_at 
		FROM tora_erel.tguvot tguvot
		WHERE tguvot.userid=$gfc_userid_quoted AND (tguvot.deleted_at IS NULL OR tguvot.deleted_at<2000)
		ORDER BY created_at DESC
		");

	if (!sql_num_rows($rows))
		return;
	print "
		<h2 style='clear:both'>תגובות אחרונות</h2>
		<table class='tguvot'>
			<col class='tguva' />
			<col class='created_at' />
		<thead>
			<tr class='heading'>
				<th>תאריך</th>
				<th>תוכן</th>
			</tr>
		</thead>
		<tbody>
		";

	$parents_already_linked = array();
	$parity = 0;
	while ($row = sql_fetch_assoc($rows)) {
		$parent = $row['parent'];
		if (isset($parents_already_linked[$parent]))
			continue;
		$parents_already_linked[$parent]=TRUE;
		$link = "/$parent";
		$text = mb_substr($row['body'],0,200)."...";
		print "
		<tr class='tguva parity$parity'>
			<td class='author'>
				$row[created_at]
			</td>
			<td class='body'>
				<a href='$link'>$text</a>
			</td>
		</tr><!--tguva-->
			";
		$parity = 1-$parity;
	}
	print "
		</tbody>
		</table>
		";
}

function external_profiles_html($id) {
	require_once(dirname(__FILE__)."/../../sites/AnExternalSiteIdentity.php");
	$html = '';
	foreach (getExternalIdentities($id) as $external_identity_data) {
		$external_site = $external_identity_data['external_site'];
		$external_userid = $external_identity_data['external_userid'];
		if ($external_site=='Facebook') {
			$html .= "
				<a target='_blank' href='http://www.facebook.com/profile.php?id=$external_userid'>
				".static_text($external_site)."
				</a>
				";
		}
	}
	return $html;
}

function show_adventurer($internal_userid) {
	$internal_userid_quoted = quote_all($internal_userid);
	$data = user_data($internal_userid_quoted);
	if ($data) {
		print "<h1>".$data['name']."</h1>";
		print user_image($data);
		show_adventurer_stats($internal_userid_quoted);
		show_treasure_stats($internal_userid_quoted);
		show_virtue_stats($internal_userid_quoted);
	}

	$external_profiles_html = external_profiles_html($internal_userid);
	if ($external_profiles_html)
		print "
			<div style='clear:both; text-align:center; border-top: black dashed 1px; margin-top:10px; padding-top:10px'/>
			".static_text('profile of at',NULL,$data['name']).":
			$external_profiles_html
			</div>
			";
	else
		print "<br style='clear:both' />";
}


if (basename(__FILE__)==basename($_SERVER['PHP_SELF'])) {
	show_html_header('adventurer');
	require_once('world.php');
	$world = secnodary_object_from_get_or_session('World');
	print "<a class='back' href='world.php'>".($world->title_for_display? $world->title_for_display: 'העולם')."</a>";

	if (!empty($_GET['id'])) {
		$internal_userid = $_GET['id'];
		$gfc_userids = getExternalSiteUserids('Gfc',$internal_userid);
	} elseif (!empty($_GET['gfc_userid'])) {
		$gfc_userids = array($_GET['gfc_userid']);
		$internal_userid = getValidatedInternalUserid('Gfc',$_GET['gfc_userid']);
	}

	if ($internal_userid) {
		show_adventurer($internal_userid);
	}
	if ($gfc_userids) {
		show_tguvot($gfc_userids[0]);
	}

	show_html_footer(/*$switch_view=*/false);
}

?>