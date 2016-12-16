<?php

$user_guid = get_input('user_guid', $vars);
$group_guid = get_input('group_guid', $vars);

$user = get_entity($user_guid);
$group = get_entity($group_guid);

if (!$user instanceof ElggUser || !$group instanceof ElggGroup) {
	register_error(elgg_echo('groups:membership:bad_request'));
	forward(REFERRER);
}

if (!$group->isMember($user) || $user->guid == elgg_get_logged_in_user_guid() || !$group->canEdit()) {
	register_error(elgg_echo('groups:membership:permission_denied'));
	forward(REFERRER);
}

if (remove_entity_relationship($user->guid, 'group_admin', $group->guid)) {
	system_message(elgg_echo('groups:membership:remove_admin:success'));
} else {
	register_error(elgg_echo('groups:membership:remove_admin:error'));
}