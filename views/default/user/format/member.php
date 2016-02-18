<?php

$size = elgg_extract('size', $vars, 'small');
$entity = elgg_extract('entity', $vars);
$group = elgg_extract('group', $vars, elgg_get_page_owner_entity());
if (!$entity instanceof ElggUser || !$group instanceof ElggGroup) {
	echo elgg_view('user/elements/summary', $vars);
	return;
}

$subtitle = array();

if ($group->owner_guid == $entity->guid || check_entity_relationship($entity->guid, 'group_admin', $group->guid)) {
	$subtitle['group_admin'] = elgg_echo('user:membership:group_admin');
}

if ($member = check_entity_relationship($entity->guid, 'member', $group->guid)) {
	$subtitle[] = elgg_echo('user:membership:member_since', [date('j M, Y', $member->time_created)]);
} else if ($request = check_entity_relationship($entity->guid, 'membership_request', $group->guid)) {
	$subtitle[] = elgg_echo('user:membership:requested', [date('j M, Y', $request->time_created)]);
} else if ($invite = check_entity_relationship($group->guid, 'invited', $entity->guid)) {
	$subtitle[] = elgg_echo('user:membership:invited', [date('j M, Y', $invite->time_created)]);
}

$last_action = max($entity->last_action, $entity->last_login, $entity->time_created);
if ($last_action) {
	$subtitle['last_action'] = elgg_echo('user:membership:last_action', [elgg_get_friendly_time($last_action)]);
}

$menu_params = $vars;
$menu_params['sort_by'] = 'priority';
$menu_params['class'] = 'elgg-menu-user-membership';
$menu = elgg_view_menu('membership', $menu_params);

$metadata = '';
if (!elgg_in_context('widgets')) {
	$menu_params['class'] = 'elgg-menu-hz';
	$metadata = elgg_view_menu('entity', $menu_params);
}


$title = null;
$query = elgg_extract('query', $vars, get_input('query'));
if ($query && elgg_is_active_plugin('search')) {
	$name = search_get_highlighted_relevant_substrings($entity->getDisplayName(), $query);
	$username = search_get_highlighted_relevant_substrings(strtolower($entity->username), $query);
	$title = elgg_view('output/url', array(
		'href' => $entity->getURL(),
		'text' => "$name (<small>@$username</small>)",
	));
}

$subtitle = elgg_trigger_plugin_hook('subtitle', 'user', $vars, $subtitle);

$subtitle_str = '';
foreach ($subtitle as $s) {
	$subtitle_str .= elgg_format_element('span', ['class' => 'elgg-member-subtitle-element'], $s);
}

if ($entity->briefdescription) {
	$view_subtitle = $subtitle_str . '<br />' . $entity->briefdescription;
} else {
	$view_subtitle = $subtitle_str;
}

$icon = elgg_view_entity_icon($entity, $size);
$summary = elgg_view('user/elements/summary', array(
	'entity' => $entity,
	'title' => $title,
	'metadata' => $metadata,
	'content' => $menu,
	'subtitle' => $view_subtitle,
		));

echo elgg_view_image_block($icon, $summary, array(
	'class' => 'elgg-user-member',
));
?>
<script>
	require(['user/format/member']);
</script>