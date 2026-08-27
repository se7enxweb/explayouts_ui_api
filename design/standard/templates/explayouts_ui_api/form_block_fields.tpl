<div class="layouts-form">
<form method="post" action="{$action_url}" class="block-form" name="design_edit">
    <input type="hidden" name="ezxform_token" value="{$ezxform_token|wash()}" />

    {if count( $item_view_types )|gt( 0 )}
    <div class="master-slave-selects">
        <div class="row-input">
            <label for="design_edit_view_type">View type</label>
            <select id="design_edit_view_type" name="view_type" class="master view-type js-skip-on-change">
                {foreach $view_types as $view_type}
                    <option value="{$view_type|wash()}" {if eq( $view_type, $block.view_type )}selected="selected"{/if}>{$view_type|wash()}</option>
                {/foreach}
            </select>
        </div>

        <div class="row-input">
            <label for="design_edit_item_view_type">Item view type</label>
            <select id="design_edit_item_view_type" name="item_view_type" class="slave">
                {foreach $item_view_types as $item_view_type => $item_view_label}
                    {def $master_list = 'list,list_numbered,grid,grid_featured'}
                    {switch match=$item_view_type}
                        {case in=array('line','mini','listitem','listitem_with_intro')}
                            {set $master_list = 'list,list_numbered'}
                        {/case}
                        {case in=array('zigzag')}
                            {set $master_list = 'list_zigzag'}
                        {/case}
                        {case in=array('accordion')}
                            {set $master_list = 'list_accordion'}
                        {/case}
                        {case}
                            {set $master_list = 'list,list_numbered,grid,grid_featured'}
                        {/case}
                    {/switch}
                    {def $is_in_master = $master_list|contains( $block.view_type )}
                    <option value="{$item_view_type|wash()}" data-master="{$master_list|wash()}" {if eq( $item_view_type, $block.item_view_type )}selected="selected"{/if} {if not( $is_in_master )}class="hidden"{/if}>{$item_view_label|wash()}</option>
                    {undef $is_in_master}
                    {undef $master_list}
                {/foreach}
            </select>
        </div>
    </div>
    {/if}

    {foreach $parameters as $param_name => $param}
        {def $param_value = cond( is_set( $parameter_values[$param_name] ), $parameter_values[$param_name], '' )}
        {include uri='design:explayouts_ui_api/form_block_parameter.tpl' param_name=$param_name param=$param value=$param_value parameter_values=$parameter_values}
        {undef $param_value}
    {/foreach}
</form>
</div>
