<!DOCTYPE html>
<html lang="{$locale|wash}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rules</title>
    <link rel="stylesheet" type="text/css" href={'stylesheets/netgen-layouts.css'|ezdesign('no')} />
    {literal}
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .ngl-admin { max-width: 1200px; margin: 0 auto; background: #fff; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #ddd; }
        th { background: #333; color: #fff; }
        .tag { display: inline-block; background: #e0e0e0; padding: 2px 6px; margin: 2px; border-radius: 3px; font-size: 12px; }
    </style>
    {/literal}
</head>
<body>
    <div class="ngl-admin">
        <h1>Rules</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Layout</th>
                    <th>Priority</th>
                    <th>Enabled</th>
                    <th>Targets</th>
                    <th>Conditions</th>
                </tr>
            </thead>
            <tbody>
                {foreach $rules as $rule}
                    <tr>
                        <td>{$rule.id}</td>
                        <td>{$rule.layout_name|wash}</td>
                        <td>{$rule.priority}</td>
                        <td>{if $rule.enabled}Yes{else}No{/if}</td>
                        <td>
                            {foreach $rule.targets as $target}
                                <span class="tag">{$target.target_type|wash}: {$target.target_value|wash}</span>
                            {/foreach}
                        </td>
                        <td>
                            {foreach $rule.conditions as $condition}
                                <span class="tag">{$condition.condition_type|wash}: {$condition.condition_value|wash}</span>
                            {/foreach}
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</body>
</html>
