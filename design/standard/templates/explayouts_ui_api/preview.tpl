<!DOCTYPE html>
<html lang="{$locale|wash}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$layout.name|wash} - Preview</title>
    <link rel="stylesheet" type="text/css" href={'stylesheets/netgen-layouts.css'|ezdesign('no')} />
    {literal}
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .ngl-preview-header { margin-bottom: 20px; }
        .ngl-preview { max-width: 1200px; margin: 0 auto; background: #fff; padding: 20px; }
        .zone-preview { min-height: 80px; border: 1px dashed #ccc; margin-bottom: 20px; padding: 10px; }
        .block-preview { margin: 10px 0; }
        .block-content { background: #fff; color: #333; padding: 8px; }
        .block-items { margin: 0; padding-left: 1.2em; }
        .block-items li { background: #fff; color: #333; padding: 2px 0; }
    </style>
    {/literal}
</head>
<body>
    <div class="ngl-preview">
        <div class="ngl-preview-header">
            <h1>{$layout.name|wash}</h1>
            <p>Layout type: <strong>{$layout.layout_type|wash}</strong></p>
        </div>
        {foreach $zones as $zone}
            <div class="zone-preview zone-{$zone.identifier|wash}">
                <h4>Zone: {$zone.identifier|wash}</h4>
                {foreach $zone.blocks as $block}
                    <div class="block-preview block-{$block.definition_identifier|wash}">
                        {$block.html}
                    </div>
                {/foreach}
            </div>
        {/foreach}
    </div>
</body>
</html>
