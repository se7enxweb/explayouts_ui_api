<div id="aside-tabs">
    <ul class="aside-tab-control" role="tablist">
        <li class="active"><a href="#" id="tab-block" role="tab" aria-controls="tab-block-tab">Block</a></li>
        <li><a href="#" id="tab-collection" role="tab" aria-controls="tab-collection-tab">Collection</a></li>
    </ul>

    <div class="tab-pane active" id="tab-block-tab">
        {if and( $collection, or( eq( $collection.collection_type, 'manual' ), eq( $collection.collection_type, 'dynamic' ) ) )}
            <div class="xeditable" data-xeditable-name="collection_type">
                <div class="current">
                    <label>Collection type</label>
                    <a href="#" class="js-edit">
                        <span class="text">{if eq( $collection.collection_type, 'manual' )}Manual collection{else}Dynamic collection{/if}</span>
                        <span class="icon">Change</span>
                    </a>
                </div>
                <div class="form">
                    <div>
                        <label for="collection-type">Collection type</label>
                        <select id="collection-type" name="block_collection[new_type]" class="js-skip-on-change js-master js-always-show">
                            <option {if eq( $collection.collection_type, 'manual' )}selected="selected"{/if} value="manual">Manual collection</option>
                            <option {if eq( $collection.collection_type, 'dynamic' )}selected="selected"{/if} value="dynamic">Dynamic collection</option>
                        </select>
                        <p class="input-note">Changing the collection type will remove existing manual items.</p>
                    </div>
                    <div class="actions">
                        <a href="#" class="btn btn-link js-cancel">Cancel</a>
                        <a href="#" class="btn btn-primary js-apply">Apply</a>
                    </div>
                </div>
            </div>
        {/if}

        <div data-form="{$form_url}"></div>
    </div>

    <div class="tab-pane" id="tab-collection-tab">
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
</div>
