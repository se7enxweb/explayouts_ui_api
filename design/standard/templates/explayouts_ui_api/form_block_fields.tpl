<form method="post" action="{$action_url}" class="block-form">
    <input type="hidden" name="ezxform_token" value="{$ezxform_token|wash()}" />
    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" value="{$block.name|wash()}" class="form-control" />
    </div>
    <div class="form-group">
        <label>View type</label>
        <select name="view_type" class="form-control">
            {foreach $view_types as $view_type}
                <option value="{$view_type}" {if eq($view_type,$block.view_type)}selected="selected"{/if}>{$view_type}</option>
            {/foreach}
        </select>
    </div>
    {foreach $parameters as $name => $parameter}
        <div class="form-group">
            <label>{$parameter.name|wash()}</label>
            {if eq($parameter.type,'textarea')}
                <textarea name="parameters[{$name}]" class="form-control">{$parameter_values[$name]|wash()}</textarea>
            {else}
                <input type="text" name="parameters[{$name}]" value="{$parameter_values[$name]|wash()}" class="form-control" />
            {/if}
        </div>
    {/foreach}
    <button type="submit" class="btn btn-primary" style="margin-top:10px;">Save block</button>
</form>
