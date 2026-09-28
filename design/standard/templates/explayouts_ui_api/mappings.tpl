<!DOCTYPE html>
<html lang="{$locale|wash}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{'Layout Mappings'|i18n( 'design/standard/explayouts_ui_api/mappings' )}</title>
    <link rel="stylesheet" type="text/css" href={'stylesheets/netgen-layouts.css'|ezdesign('no')} />
    {literal}
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .ngl-admin { max-width: 1200px; margin: 0 auto; background: #fff; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #ddd; }
        th { background: #333; color: #fff; }
    </style>
    {/literal}
</head>
<body>
    <div class="ngl-admin">
        <h1>{'Layout Mappings'|i18n( 'design/standard/explayouts_ui_api/mappings' )}</h1>
        <table>
            <thead>
                <tr>
                    <th>{'Layout ID'|i18n( 'design/standard/explayouts_ui_api/mappings' )}</th>
                    <th>{'Layout name'|i18n( 'design/standard/explayouts_ui_api/mappings' )}</th>
                    <th>{'Rules mapped'|i18n( 'design/standard/explayouts_ui_api/mappings' )}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $mappings as $mapping}
                    <tr>
                        <td>{$mapping.layout_id}</td>
                        <td>{$mapping.layout_name|wash}</td>
                        <td>{$mapping.count}</td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</body>
</html>
