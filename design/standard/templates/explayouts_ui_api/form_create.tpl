<div class="modal-dialog new_layout">
    <div class="modal-content">
        <div class="modal-body">
            <form method="post" action="{$action_url}">
                <div class="form-group">
                    <label>Layout type</label>
                    <div class="layout-type-grid">
                        {foreach $layout_types as $type}
                            <label class="layout-type-option">
                                <input type="radio" name="layout_type" value="{$type.identifier|wash()}" {if eq($type.identifier,$layout_types.0.identifier)}checked="checked"{/if} />
                                <span class="layout-type-thumb">
                                    <svg viewBox="0 0 100 80" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="0" y="0" width="100" height="80" fill="#d4d4d4"/>
                                        <rect x="4" y="4" width="92" height="8" fill="#fff"/>
                                        <rect x="4" y="16" width="92" height="60" fill="#e9e9e9"/>
                                        <rect x="8" y="20" width="84" height="8" fill="#fff" opacity="0.6"/>
                                        <rect x="8" y="32" width="40" height="38" fill="#fff" opacity="0.6"/>
                                        <rect x="52" y="32" width="40" height="38" fill="#fff" opacity="0.6"/>
                                    </svg>
                                </span>
                                <span class="layout-type-name">{$type.name|wash()}</span>
                            </label>
                        {/foreach}
                    </div>
                </div>
                {literal}<style>
                    .layout-type-grid { display: flex; flex-wrap: wrap; justify-content: center; margin: 0 -6px 1rem; }
                    .layout-type-option { width: 20%; min-width: 70px; padding: 0 6px; margin: 0 0 10px; text-align: center; cursor: pointer; box-sizing: border-box; }
                    .layout-type-option input { position: absolute; opacity: 0; }
                    .layout-type-thumb { display: block; border: 2px solid #d3d3d3; padding: 2px; margin: 0 auto 4px; background: #fff; max-width: 80px; }
                    .layout-type-option input:checked + .layout-type-thumb { border-color: #2970ef; }
                    .layout-type-thumb svg { display: block; margin: 0 auto; height: 50px; width: auto; }
                    .layout-type-name { font-size: 11px; color: #404040; }
                </style>{/literal}

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" class="form-control" />
                </div>
                <div class="form-group">
                    <label for="identifier">Identifier</label>
                    <input type="text" name="identifier" id="identifier" class="form-control" />
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control"></textarea>
                </div>
                <div class="form-group checkbox">
                    <label>
                        <input type="checkbox" name="isShared" id="create_isShared" value="1" />
                        Shared layout
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary action_apply green">Create layout</button>
        </div>
    </div>
</div>
