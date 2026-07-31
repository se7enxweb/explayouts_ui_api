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
    <button type="button" class="btn btn-primary js-save-block" style="margin-top:10px;">Save block</button>
</form>
{literal}<script>
(function(){
    var form = document.querySelector('.block-form');
    var btn = document.querySelector('.js-save-block');
    if ( !form || !btn ) return;
    btn.addEventListener('click', function(e){
        e.preventDefault();
        var data = new URLSearchParams(new FormData(form));
        fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin' }).then(function(r){
            if ( !r.ok ) throw new Error('Save failed');
            return r.json();
        }).then(function(){
            btn.textContent = 'Saved';
            setTimeout(function(){ btn.textContent = 'Save block'; }, 1500);
        }).catch(function(){
            btn.textContent = 'Error';
            setTimeout(function(){ btn.textContent = 'Save block'; }, 2000);
        });
    });
})();
</script>{/literal}
