<form method="post" action="{$action_url}" class="content-form block-form layouts-form" name="content_edit">
    <input type="hidden" name="ezxform_token" value="{$ezxform_token|wash()}" />
    <div class="row-input">
        <label for="content_edit_name">Block label</label>
        <input type="text" id="content_edit_name" name="name" value="{$block_name|wash()}" class="form-control" />
    </div>
</form>
