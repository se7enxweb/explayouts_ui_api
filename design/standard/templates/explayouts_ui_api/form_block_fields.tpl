<div class="layouts-form">
<form method="post" action="{$action_url}" class="block-form" name="design_edit">
    <input type="hidden" name="ezxform_token" value="{$ezxform_token|wash()}" />

    <div class="row-input">
        <label for="design_edit_block_name">{'Block label'|i18n( 'design/standard/explayouts_ui_api/form_block_fields' )}</label>
        <input type="text" id="design_edit_block_name" name="name" value="{$block_name|wash()}" class="form-control" />
    </div>

    {if count( $item_view_types )|gt( 0 )}
    <div class="master-slave-selects">
        <div class="row-input">
            <label for="design_edit_view_type">{'View type'|i18n( 'design/standard/explayouts_ui_api/form_block_fields' )}</label>
            <select id="design_edit_view_type" name="view_type" class="master view-type js-skip-on-change">
                {foreach $view_types as $view_type}
                    <option value="{$view_type|wash()}" {if eq( $view_type, $block.view_type )}selected="selected"{/if}>{if and( is_set( $view_type_names ), is_set( $view_type_names[$view_type] ) )}{$view_type_names[$view_type]|i18n( 'design/standard/explayouts_ui_api/parameters' )|wash()}{else}{$view_type|wash()}{/if}</option>
                {/foreach}
            </select>
        </div>

        <div class="row-input">
            <label for="design_edit_item_view_type">{'Item view type'|i18n( 'design/standard/explayouts_ui_api/form_block_fields' )}</label>
            <select id="design_edit_item_view_type" name="item_view_type" class="slave">
                {* Item view types are an axis of their own: every view type of the definition offers all of them (the
                   handler's list and ItemViewTypes[] of [BlockDefinition_*]), so data-master names every view type.
                   The fixed switch of the list block's view types that stood here hid the items of other view types,
                   grid_tiles of a design among them. *}
                {def $master_list = $view_types|implode( ',' )}
                {* No item view type, the default of a new block: the design draws the view type's own (the media
                   design reads '' so). Without this option the browser selected the first one, and saving a new block
                   stored it unasked, a gallery's slider then drew standard teasers instead of its slides. *}
                <option value="" data-master="{$master_list|wash()}" {if first_set( $block.item_view_type, '' )|eq( '' )}selected="selected"{/if}>{'Default of the view type'|i18n( 'design/standard/explayouts_ui_api/form_block_fields' )|wash()}</option>
                {foreach $item_view_types as $item_view_type => $item_view_label}
                    <option value="{$item_view_type|wash()}" data-master="{$master_list|wash()}" {if eq( $item_view_type, $block.item_view_type )}selected="selected"{/if}>{$item_view_label|i18n( 'design/standard/explayouts_ui_api/parameters' )|wash()}</option>
                {/foreach}
                {* A stored item view type the list does not offer stays selected as it is: without this option the
                   browser selected the first one and saving the block changed its item view unasked. *}
                {if and( $block.item_view_type|ne( '' ), is_set( $item_view_types[$block.item_view_type] )|not )}
                    <option value="{$block.item_view_type|wash()}" data-master="{$master_list|wash()}" selected="selected">{$block.item_view_type|wash()}</option>
                {/if}
                {undef $master_list}
            </select>
        </div>
    </div>
    {elseif count( $view_types )|gt( 1 )}
    <div class="row-input">
        <label for="design_edit_view_type">{'View type'|i18n( 'design/standard/explayouts_ui_api/form_block_fields' )}</label>
        <select id="design_edit_view_type" name="view_type" class="view-type js-skip-on-change">
            {foreach $view_types as $view_type}
                <option value="{$view_type|wash()}" {if eq( $view_type, $block.view_type )}selected="selected"{/if}>{if and( is_set( $view_type_names ), is_set( $view_type_names[$view_type] ) )}{$view_type_names[$view_type]|i18n( 'design/standard/explayouts_ui_api/parameters' )|wash()}{else}{$view_type|wash()}{/if}</option>
            {/foreach}
        </select>
    </div>
    {/if}

    {foreach $parameters as $param_name => $param}
        {def $param_value = cond( is_set( $parameter_values[$param_name] ), $parameter_values[$param_name], '' )}
        {include uri='design:explayouts_ui_api/form_block_parameter.tpl' param_name=$param_name param=$param value=$param_value parameter_values=$parameter_values}
        {undef $param_value}
    {/foreach}
</form>
</div>
