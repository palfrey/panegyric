<?php
function panegyric_update()
{
    global $wpdb;
    $day_ago = new DateTime();
    $day_ago = $day_ago->sub(new DateInterval("P1D"));
    $beginning = new DateTime("@1");

    $org_table = new Panegyric_Organisations_List_Table();
    $orgs = $wpdb->get_results("select org, updated from {$wpdb->prefix}panegyric_org");
    foreach ($orgs as $org) {
        $when = $org->updated ? DateTime::createFromFormat('Y-m-d H:i:s', $org->updated) : $beginning;
        if ($when > $day_ago) {
            error_log("{$org->org} is sufficiently up to date");
            continue;
        }
        error_log("Updating {$org->org}");
        $org_table->update_item('org', $org->org);
    }

    $users_table = new Panegyric_Users_List_Table();
    $users = $wpdb->get_results("select username, updated, prs_updated from {$wpdb->prefix}panegyric_users");
    foreach ($users as $user) {
        $when = $user->updated ? DateTime::createFromFormat('Y-m-d H:i:s', $user->updated) : $beginning;
        if ($when > $day_ago) {
            error_log("{$user->username} is sufficiently up to date");
        } else {
            error_log("Updating {$user->username}");
            $users_table->update_item('user', $user->username);
        }

        $when = $user->prs_updated ? DateTime::createFromFormat('Y-m-d H:i:s', $user->prs_updated) : $beginning;
        if ($when > $day_ago) {
            error_log("{$user->username}'s PRs are sufficiently up to date");
        } else {
            error_log("Updating {$user->username}'s PRs");
            $users_table->update_item('prs', $user->username);
        }
    }
}
