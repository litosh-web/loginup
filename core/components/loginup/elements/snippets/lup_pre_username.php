<?php
$new_login = $hook->getValue('username');
$new_login = preg_replace('/\s+/', '', $new_login);
$user = $modx->getUser();

$user_id = $user->get('id');
$last_login = $user->get('username');

$total = $modx->getCount('modUser', [
    'username' => $new_login,
    'id:!=' => $user_id
]);

if ($total) {
    $hook->addError('user', $modx->lexicon('lup_message_username_exist'));
    return $hook->hasErrors();
} else {
    $user->set('username', $new_login);
    return true;
}