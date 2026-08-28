<div id="aside-tabs">
    <ul class="aside-tab-control" role="tablist">
        <li class="active"><a href="#" id="tab-content" role="tab" aria-controls="tab-content-tab">Content</a></li>
        <li><a href="#" id="tab-design" role="tab" aria-controls="tab-design-tab">Design</a></li>
    </ul>

    <div class="tab-pane active" id="tab-content-tab">
        <div class="sidebar-panel">
            <a class="toggle-link" role="button" data-toggle="collapse" href="#collapseSettings" aria-expanded="true" aria-controls="collapseSettings">Options</a>
            <div class="collapse in" id="collapseSettings">
                <div data-form="{$content_form_url}"></div>

                {if and( $collection, or( eq( $collection.collection_type, 'manual' ), eq( $collection.collection_type, 'dynamic' ) ) )}
                    <div class="xeditable" data-xeditable-name="collection_type">
                        <div class="current">
                            <label>Collection type</label>
                            <a href="#" class="js-edit">
                                <span class="text">{if eq( $collection.collection_type, 'manual' )}Manual collection{else}Dynamic collection{/if}</span>
                                <span class="icon">Change</span>
                            </a>
                            {if eq( $collection.collection_type, 'dynamic' )}
                                <label>Query type</label>
                                <a href="#" class="js-edit">
                                    <span class="text">{if $collection.query_type}{$collection.query_type|wash()}{else}Ibexa{/if}</span>
                                    <span class="icon">Change</span>
                                </a>
                            {/if}
                        </div>
                        <div class="form js-dependable-selects-group">
                            <div>
                                <label for="collection-type">Collection type</label>
                                <select id="collection-type" name="block_collection[new_type]" class="form-control js-skip-on-change js-master js-always-show">
                                    <option {if eq( $collection.collection_type, 'manual' )}selected="selected"{/if} value="manual">Manual collection</option>
                                    <option {if eq( $collection.collection_type, 'dynamic' )}selected="selected"{/if} value="dynamic">Dynamic collection</option>
                                </select>
                                <p class="input-note">Changing the collection type will remove existing manual items.</p>
                            </div>
                            <div data-linked-value="dynamic">
                                <label for="query-type">Query type</label>
                                <select id="query-type" name="block_collection[query_type]" class="form-control js-skip-on-change js-always-show">
                                    <option {if eq( $collection.query_type, 'exponential_content_search' )}selected="selected"{/if} value="exponential_content_search">Exponential</option>
                                    <option {if eq( $collection.query_type, 'content_by_topic' )}selected="selected"{/if} value="content_by_topic">Topics</option>
                                </select>
                            </div>
                            <div class="actions">
                                <a href="#" class="btn btn-link js-cancel">Cancel</a>
                                <a href="#" class="btn btn-primary js-apply">Apply</a>
                            </div>
                        </div>
                    </div>
                {/if}

                {if and( $collection, eq( $collection.collection_type, 'dynamic' ), $query_form_url )}
                    <div data-form="{$query_form_url}" data-query-form="true"></div>
                {/if}
            </div>
        </div>

        <div class="sidebar-panel">
            <a class="toggle-link" role="button" data-toggle="collapse" href="#collapseItems" aria-expanded="true" aria-controls="collapseItems">Items</a>
            <div class="collapse in" id="collapseItems">
                <div class="collection-items">
                    <div class="value-type-wrapper">
                        <select class="js-browser-item-type">
                            <option value="ez_location" data-min="0" data-max="100">eZ location</option>
                        </select>
                    </div>
                    <div class="body"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane" id="tab-design-tab">
        <div data-form="{$form_url}"></div>
    </div>
</div>
