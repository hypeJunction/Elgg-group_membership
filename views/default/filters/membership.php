<?php

$identifier = elgg_extract('identifier', $vars, 'groups');
$filter_context = elgg_extract('filter_context', $vars, 'members');
$entity = elgg_extract('entity', $vars);

$tabs = [
	'members' => "{$identifier}/members/{$entity->guid}",
];

if ($entity->canEdit()) {
	if (!$entity->isPublicMembership()) {
		$tabs['requests'] = "{$identifier}/requests/{$entity->guid}";
	}
	$tabs['invited'] = "{$identifier}/invited/{$entity->guid}";
}
if ($entity->canEdit() || ($entity->isMember() && $entity->invites_enable == 'yes')) {
	$tabs['invite'] = "{$identifier}/invite/{$entity->guid}";
}

foreach ($tabs as $tab => $url) {
	elgg_register_menu_item('filter', array(
		'name' => "$identifier:list:$tab",
		'text' => elgg_echo("$identifier:list:$tab"),
		'href' => elgg_normalize_url($url),
		'selected' => $tab == $filter_context,
	));
}

$params = $vars;
$params['sort_by'] = 'priority';
echo elgg_view_menu('filter', $params);
