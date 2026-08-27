{* Recursively render one design parameter for the Nexus-style Design tab. *}
{def $name = cond( is_set( $param_name ), $param_name, '' )}
{def $input_id = concat( 'design_edit_parameters_', $name|md5() )}
{def $view_type_attr = cond( is_set( $param.view_type ), $param.view_type, '' )}
{def $is_compound = cond( and( is_set( $param.type ), eq( $param.type, 'compound_checkbox' ) ), true(), false() )}
{def $is_checkbox = cond( and( is_set( $param.type ), or( eq( $param.type, 'checkbox' ), eq( $param.type, 'compound_checkbox' ) ) ), true(), false() )}
{def $type = cond( is_set( $param.type ), $param.type, 'string' )}
{def $use_self = cond( and( is_set( $param.no_self ), $param.no_self ), false(), true() )}

{if eq( $type, 'select' )}
    <div class="row-input" {if $view_type_attr}data-view-type="{$view_type_attr|wash()}"{/if}>
        <label for="{$input_id}">{$param.name|wash()}</label>
        <select id="{$input_id}" name="parameters[{$name}]" class="js-skip-on-change">
            {foreach $param.options as $opt_val => $opt_label}
                <option value="{$opt_val|wash()}" {if and( is_set( $value ), eq( $opt_val, $value ) )}selected="selected"{/if}>{$opt_label|wash()}</option>
            {/foreach}
        </select>
    </div>
{elseif eq( $type, 'integer' )}
    <div class="row-input" {if $view_type_attr}data-view-type="{$view_type_attr|wash()}"{/if}>
        <label for="{$input_id}">{$param.name|wash()}</label>
        <input type="number" id="{$input_id}" name="parameters[{$name}]" value="{if is_set( $value )}{$value|wash()}{/if}" />
    </div>
{elseif $is_checkbox}
    <div {if $view_type_attr}data-view-type="{$view_type_attr|wash()}"{/if}{if $is_compound} data-compound-checkbox="true"{if and( is_set( $param.reverse ), $param.reverse )} data-compound-reverse="true"{/if}{/if}>
        <div class="checkbox row-input">
            {if $is_compound}
                {if $use_self}
                    <input type="hidden" name="parameters[{$name}][_self]" value="0" />
                    <input type="checkbox" id="{$input_id}__self" name="parameters[{$name}][_self]" value="1" {if and( is_set( $value ), $value )}checked="checked"{/if} />
                    <label for="{$input_id}__self">{$param.name|wash()}</label>
                {else}
                    <input type="hidden" name="parameters[{$name}]" value="0" />
                    <input type="checkbox" id="{$input_id}" name="parameters[{$name}]" value="1" {if and( is_set( $value ), $value )}checked="checked"{/if} />
                    <label for="{$input_id}">{$param.name|wash()}</label>
                {/if}
            {else}
                <input type="hidden" name="parameters[{$name}]" value="0" />
                <input type="checkbox" id="{$input_id}" name="parameters[{$name}]" value="1" {if and( is_set( $value ), $value )}checked="checked"{/if} />
                <label for="{$input_id}">{$param.name|wash()}</label>
            {/if}
            {if and( $is_compound, is_set( $param.children ) )}
                <div class="children">
                    {foreach $param.children as $child_name => $child}
                        {def $child_value = cond( is_set( $parameter_values[$child_name] ), $parameter_values[$child_name], '' )}
                        {include uri='design:explayouts_ui_api/form_block_parameter.tpl' param_name=$child_name param=$child value=$child_value parameter_values=$parameter_values}
                        {undef $child_value}
                    {/foreach}
                </div>
            {/if}
        </div>
    </div>
{elseif eq( $type, 'browse' )}
    {def $item_type = cond( eq( $name, 'parent_location_id' ), 'ibexa_location', 'ibexa_content' )
         $selected_node = false()
         $selected_object = false()
         $cms_node_id = 0
         $selected_name = 'Select item'
         $cms_url = ''
         $is_empty = true()}
    {if and( is_set( $value ), $value|ne('') )}
        {if eq( $item_type, 'ibexa_location' )}
            {set $selected_node = fetch( 'content', 'node', hash( 'node_id', $value ) )}
            {if $selected_node}
                {set $selected_name = $selected_node.name|wash()
                     $cms_node_id = $selected_node.node_id
                     $cms_url = concat( '/content/view/full/', $cms_node_id )
                     $is_empty = false()}
            {/if}
        {else}
            {set $selected_object = fetch( 'content', 'object', hash( 'object_id', $value ) )}
            {if $selected_object}
                {set $selected_name = $selected_object.name|wash()
                     $cms_node_id = $selected_object.main_node_id
                     $cms_url = concat( '/content/view/full/', $cms_node_id )
                     $is_empty = false()}
            {/if}
        {/if}
    {/if}
    <div class="row-input" {if $view_type_attr}data-view-type="{$view_type_attr|wash()}"{/if}>
        <label for="{$input_id}">{$param.name|wash()}</label>
        <div class="input-browse {if $is_empty}item-empty{/if} js-input-browse exp-input-browse" data-browser="true" data-input-id="{$input_id}">
            <a href="#" class="js-trigger" title="Browse content">
                <span class="js-name" data-empty-note="Select item">{$selected_name|wash()}</span>
                <i class="material-icons">folder_open</i>
            </a>
            <a href="#" class="js-clear" title="Clear selection">
                <i class="material-icons">close</i>
            </a>
            <input type="hidden" class="js-item-type" value="{$item_type|wash()}" />
            <input type="hidden" class="js-value" id="{$input_id}" name="parameters[{$name}]" value="{if is_set( $value )}{$value|wash()}{/if}" />
        </div>
        {if $cms_url|ne('')}
            <a href="{$cms_url|ezurl('no')}" target="_blank" class="js-view-cms">View in CMS</a>
        {/if}
    </div>
    {undef $item_type $selected_node $selected_object $cms_node_id $selected_name $cms_url $is_empty}
{elseif eq( $type, 'multiselect' )}
    <div class="row-input" {if $view_type_attr}data-view-type="{$view_type_attr|wash()}"{/if}>
        <label for="{$input_id}">{$param.name|wash()}</label>
        <select id="{$input_id}" name="parameters[{$name}][]" multiple="multiple" class="js-skip-on-change" size="10">
            {foreach $param.options as $opt_val => $opt_label}
                <option value="{$opt_val|wash()}" {if and( is_array( $value ), $value|contains( $opt_val ) )}selected="selected"{/if}>{$opt_label|wash()}</option>
            {/foreach}
        </select>
    </div>
{elseif eq( $type, 'textarea' )}
    <div class="row-input" {if $view_type_attr}data-view-type="{$view_type_attr|wash()}"{/if}>
        <label for="{$input_id}">{$param.name|wash()}</label>
        <textarea id="{$input_id}" name="parameters[{$name}]">{if is_set( $value )}{$value|wash()}{/if}</textarea>
    </div>
{else}
    <div class="row-input" {if $view_type_attr}data-view-type="{$view_type_attr|wash()}"{/if}>
        <label for="{$input_id}">{$param.name|wash()}</label>
        <input type="text" id="{$input_id}" name="parameters[{$name}]" value="{if is_set( $value )}{$value|wash()}{/if}" />
    </div>
{/if}

{undef $input_id $view_type_attr $is_compound $is_checkbox $type $use_self}
