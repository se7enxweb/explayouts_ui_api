<form method="post" action="{$action_url}" class="query-form block-form layouts-form" name="query_edit">
    <input type="hidden" name="ezxform_token" value="{$ezxform_token|wash()}" />
    <input type="hidden" name="query_type" value="{$query_type|wash()}" />

    {foreach $basic_params as $name}
        {def $param = $parameters[$name]}
        {def $value = cond( is_set( $parameter_values[$name] ), $parameter_values[$name], cond( is_set( $param.default ), $param.default, '' ) )}
        {include uri='design:explayouts_ui_api/form_block_parameter.tpl' param_name=$name param=$param value=$value parameter_values=$parameter_values}
        {undef $param $value}
    {/foreach}

    {if count( $advanced_params )}
        <div class="sidebar-panel advanced-options">
            <a class="toggle-link sub-toggle" role="button" data-toggle="collapse" href="#collapseAdvanced" aria-expanded="false" aria-controls="collapseAdvanced">Advanced options</a>
            <div class="collapse" id="collapseAdvanced">
                {foreach $advanced_params as $name}
                    {def $param = $parameters[$name]}
                    {def $value = cond( is_set( $parameter_values[$name] ), $parameter_values[$name], cond( is_set( $param.default ), $param.default, '' ) )}
                    {include uri='design:explayouts_ui_api/form_block_parameter.tpl' param_name=$name param=$param value=$value parameter_values=$parameter_values}
                    {undef $param $value}
                {/foreach}
            </div>
        </div>
    {/if}

    <div class="sidebar-panel offset-limit">
        <a class="toggle-link sub-toggle" role="button" data-toggle="collapse" href="#collapseOffsetLimit" aria-expanded="false" aria-controls="collapseOffsetLimit">Offset & number of items</a>
        <div class="collapse" id="collapseOffsetLimit">
            <div class="row-input">
                <label for="query_edit_offset">Offset</label>
                <input type="number" id="query_edit_offset" name="offset" value="{if is_set( $offset )}{$offset|wash()}{else}0{/if}" min="0" />
            </div>
            <div class="row-input">
                <label for="query_edit_limit">Number of items</label>
                <input type="number" id="query_edit_limit" name="limit" value="{if is_set( $limit )}{$limit|wash()}{else}0{/if}" min="0" />
            </div>
        </div>
    </div>
</form>
