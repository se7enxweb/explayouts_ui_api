<?php
/**
 * Gives explayouts_ui_api/edit to every role that has explayouts_ui_api/read, so the editors
 * of an installation keep working after the upgrade that made every changing request of the
 * layouts API ask for edit (explayouts_ui_api 1.3.18). A role with explayouts_ui_api/* already
 * has edit and is left alone; so is a role that has edit already. Running it again changes
 * nothing.
 *
 * Run it once after upgrading, from the installation's root:
 *
 *   php extension/explayouts_ui_api/bin/php/addeditpolicy.php --dry-run
 *   php extension/explayouts_ui_api/bin/php/addeditpolicy.php
 *
 * Add -s <siteaccess> for an installation whose database is set per siteaccess. Only roles
 * that are really meant to change layouts should keep edit: read alone still opens the editor
 * and shows the layouts. Review the roles afterwards under Setup, Roles and policies.
 *
 * @copyright Copyright (C) 1998 - 2026 Exponential Foundation and 7x. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 */
require_once 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array(
    'description' => "Gives explayouts_ui_api/edit to every role that has explayouts_ui_api/read.",
    'use-session' => false,
    'use-modules' => true,
    'use-extensions' => true,
) );
$script->startup();
$options = $script->getOptions( '[dry-run]', '', array( 'dry-run' => 'Report the roles, change nothing.' ) );
$script->initialize();

$dryRun = (bool)$options['dry-run'];
$changed = 0;
foreach ( eZRole::fetchList() as $role )
{
    $hasRead = false;
    $hasEdit = false;
    foreach ( $role->policyList() as $policy )
    {
        if ( $policy->attribute( 'module_name' ) !== 'explayouts_ui_api' )
            continue;
        $function = (string)$policy->attribute( 'function_name' );
        if ( $function === 'read' )
            $hasRead = true;
        elseif ( $function === 'edit' || $function === '*' )
            $hasEdit = true;
    }
    if ( !$hasRead || $hasEdit )
        continue;

    $cli->output( ( $dryRun ? 'Would add' : 'Adding' ) . ' explayouts_ui_api/edit to the role ' . $role->attribute( 'name' ) . ' (' . (int)$role->attribute( 'id' ) . ')' );
    if ( !$dryRun )
        $role->appendPolicy( 'explayouts_ui_api', 'edit' );
    ++$changed;
}

if ( $changed > 0 && !$dryRun )
    eZRole::expireCache();

$cli->output( $changed === 0 ? 'Nothing to do: no role has explayouts_ui_api/read without edit.'
                             : ( $dryRun ? $changed . ' role(s) would get explayouts_ui_api/edit.' : $changed . ' role(s) got explayouts_ui_api/edit.' ) );
$script->shutdown();
