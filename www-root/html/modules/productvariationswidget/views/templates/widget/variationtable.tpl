<h4>My widget: </h4>
{*{$product->height|print_r}*}
{foreach from=$groups key=id_attribute_group item=group}
    {if $group.attributes|@count && $id_attribute_group==2} {* 2=size *}
        <div class="sizes-buy-block" id="group_{$id_attribute_group}">
            <div class="size-dimension-title"> Kaz size</div>
            <div class="size-dimension-title"> Dimension</div>
            {foreach from=$group.attributes key=id_attribute item=group_attribute}
                {*<tr class="input-container float-xs-left">*}

                <label class="boxy-label">
                    <input class="input-radio" type="radio"
                           data-product-attribute="{$id_attribute_group}"
                           name="group[{$id_attribute_group}]" value="{$id_attribute}"
                           title="{$group_attribute.name}"{if $group_attribute.selected} checked="checked"{/if}>
                    <span class="radio2-label">
                                <div class="size-dimension-subelement"> {$group_attribute.name} cm </div>
                                <div class="size-dimension-subelement"> 00x00 cm</div>
                            </span>
                </label>

                {*</tr>*}
            {/foreach}
        </div>
    {/if}
{/foreach}


<div class="size-dimension" {if isset($heightBlock) && $heightBlock == 'height-big'}style="margin-bottom: 25px"{/if}>
    <div id="default-size" style="display: none">1.5</div>
    <div id="default-height" style="display: none">{$product->height}</div>
    <div id="default-width" style="display: none">{$product->width}</div>

</div>














