<form method="post" action="{$action_url}" class="block-form">
    <input type="hidden" name="ezxform_token" value="{$ezxform_token|wash()}" />

    <div class="row-input">
        <label for="block-name">Name</label>
        <input type="text" id="block-name" name="name" value="{$block.name|wash()}" class="form-control" />
    </div>

    <div class="row-input">
        <label for="block-view-type">View type</label>
        <select id="block-view-type" name="view_type" class="form-control">
            {foreach $view_types as $view_type}
                <option value="{$view_type}" {if eq($view_type,$block.view_type)}selected="selected"{/if}>{$view_type}</option>
            {/foreach}
        </select>
    </div>

    {foreach $parameters as $name => $parameter}
        <div class="row-input">
            <label for="block-param-{$name}">{$parameter.name|wash()}</label>
            {if eq($parameter.type,'textarea')}
                <textarea id="block-param-{$name}" name="parameters[{$name}]" class="form-control">{$parameter_values[$name]|wash()}</textarea>
            {else}
                <input type="text" id="block-param-{$name}" name="parameters[{$name}]" value="{$parameter_values[$name]|wash()}" class="form-control" />
            {/if}
        </div>
    {/foreach}
</form>
