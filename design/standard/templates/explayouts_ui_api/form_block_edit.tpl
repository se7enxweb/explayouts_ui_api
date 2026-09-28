<div id="aside-tabs">
    <ul class="aside-tab-control" role="tablist">
        <li><a href="#" id="tab-content" role="tab" aria-controls="tab-content-tab">{'Content'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</a></li>
        <li class="active"><a href="#" id="tab-design" role="tab" aria-controls="tab-design-tab">{'Design'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</a></li>
    </ul>

    <div class="tab-pane" id="tab-content-tab">
        <div class="sidebar-panel">
            <a class="toggle-link" role="button" data-toggle="collapse" href="#collapseSettings" aria-expanded="true" aria-controls="collapseSettings">{'Options'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</a>
            <div class="collapse in" id="collapseSettings">
                <div data-form="{$content_form_url}"></div>

                {if and( $collection, or( eq( $collection.collection_type, 'manual' ), eq( $collection.collection_type, 'dynamic' ) ) )}
                    <div class="xeditable" data-xeditable-name="collection_type">
                        <div class="current">
                            <label>{'Collection type'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</label>
                            <a href="#" class="js-edit">
                                <span class="text">{if eq( $collection.collection_type, 'manual' )}{'Manual collection'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}{else}{'Dynamic collection'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}{/if}</span>
                                <span class="icon">{'Change'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</span>
                            </a>
                            {if eq( $collection.collection_type, 'dynamic' )}
                                <label>{'Query type'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</label>
                                <a href="#" class="js-edit">
                                    <span class="text">{if $collection.query_type}{$collection.query_type|wash()}{else}Ibexa{/if}</span>
                                    <span class="icon">{'Change'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</span>
                                </a>
                            {/if}
                        </div>
                        <div class="form js-dependable-selects-group">
                            <div>
                                <label for="collection-type">{'Collection type'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</label>
                                <select id="collection-type" name="block_collection[new_type]" class="form-control js-skip-on-change js-master js-always-show">
                                    <option {if eq( $collection.collection_type, 'manual' )}selected="selected"{/if} value="manual">{'Manual collection'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</option>
                                    <option {if eq( $collection.collection_type, 'dynamic' )}selected="selected"{/if} value="dynamic">{'Dynamic collection'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</option>
                                </select>
                                <p class="input-note">{'Changing the collection type will remove existing manual items.'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</p>
                            </div>
                            <div data-linked-value="dynamic">
                                <label for="query-type">{'Query type'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</label>
                                <select id="query-type" name="block_collection[query_type]" class="form-control js-skip-on-change js-always-show">
                                    <option {if eq( $collection.query_type, 'exponential_content_search' )}selected="selected"{/if} value="exponential_content_search">Exponential</option>
                                    <option {if eq( $collection.query_type, 'content_by_topic' )}selected="selected"{/if} value="content_by_topic">{'Topics'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</option>
                                </select>
                            </div>
                            <div class="actions">
                                <a href="#" class="btn btn-link js-cancel">{'Cancel'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</a>
                                <a href="#" class="btn btn-primary js-apply">{'Apply'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</a>
                            </div>
                        </div>
                    </div>
                {/if}

                {if and( $collection, eq( $collection.collection_type, 'dynamic' ), $query_form_url )}
                    <div data-form="{$query_form_url}" data-query-form="true"></div>
                {/if}
            </div>
        </div>

        {if and( $collection, or( eq( $collection.collection_type, 'manual' ), eq( $collection.collection_type, 'dynamic' ) ) )}
        <div class="sidebar-panel">
            <a class="toggle-link" role="button" data-toggle="collapse" href="#collapseItems" aria-expanded="true" aria-controls="collapseItems">{'Items'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</a>
            <div class="collapse in" id="collapseItems">
                <div class="collection-items">
                    <div class="value-type-wrapper">
                        <select class="js-browser-item-type">
                            <option value="ez_location" data-min="0" data-max="100">{'eZ location'|i18n( 'design/standard/explayouts_ui_api/form_block_edit' )}</option>
                        </select>
                    </div>
                    <div class="body"></div>
                </div>
            </div>
        </div>
        {/if}
    </div>

    <div class="tab-pane active" id="tab-design-tab">
        <div data-form="{$form_url}"></div>
    </div>
</div>
