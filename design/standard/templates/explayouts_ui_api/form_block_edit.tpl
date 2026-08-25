<div id="aside-tabs" class="aside-tabs">
    <ul class="nav nav-tabs" role="tablist">
        <li class="active"><a href="#tab-block-tab" id="tab-block" role="tab" aria-controls="tab-block-tab" data-toggle="tab">Block</a></li>
        <li><a href="#tab-collection-tab" id="tab-collection" role="tab" aria-controls="tab-collection-tab" data-toggle="tab">Collection</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane active" id="tab-block-tab">
            <div data-form="{$form_url}"></div>
        </div>
        <div class="tab-pane" id="tab-collection-tab">
            <div class="collection-items">
                <select class="js-browser-item-type" style="display: none;">
                    <option value="ez_location" data-min="0" data-max="100">eZ location</option>
                </select>
                <div class="body"></div>
            </div>
        </div>
    </div>
</div>
