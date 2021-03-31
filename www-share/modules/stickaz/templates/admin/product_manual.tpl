<html>
    <head>

        <title>Stickaz Product Manual</title>


        {foreach $my_css as $item}
          <link rel="stylesheet" href="{$item|escape:'html':'UTF-8'}" type="text/css" />
        {/foreach}
        {foreach $my_js as $item}
          <script src="{$item|escape:'html':'UTF-8'}"></script>
        {/foreach}

<script type="text/javascript">

    $(document).ready(function() {
        var availableColors = {$jsonVars.availableColors};
        var model = {$jsonVars.model};
        var container = $('#canvas-wrapper');
        Studio.create(container, availableColors, model);
    });
</script>

</head>
<body>

    <h1 class="product-name">{$productName}</h1>

    <div id="studio-wrapper">
        <div id="canvas-wrapper"></div>
    </div>

    <div class="used-color-list">
        {foreach $usedColors as $color}
            <div class="used-color">
                <div
                    class="color-box"
                    style="background-color: {$color.color}; border: 1px solid {if $color.color == '#F8F9FB'}#999{else}{$color.color}{/if}"
                ></div>
                <div class="color-count">{$color.quantity}</div>
            </div>
        {/foreach}
    </div>

    <div id="color-shipping">
        <div id="palette-colors">
            <table id="color-list">
                <tr id="header">
                    <th class="color">Color name</th>
                    <th class="colorcode">Color code</th>
                    <th class="colorcount">Broj bez 10%</th>
                    <th class="packscount">Paketa od 9 sa10%</th>
                    <th class="packscount">Dodatno kaz[9]</th>
                    <th class="packscount">Paketa od 4sa10%</th>
                    <th class="packscount">Dodatno kaz[4]</th>
                </tr>
                {foreach $shippingColors as $color}
                    <tr id="c{$color.code}" class="colorline">
                        <td class="colorname" style="background-color: {$color.color};">
                            <span>{$color.name}</span>
                        </td>
                        <td class="colorcode">{$color.code}</td>
                        <td class="colorcount">{$color.counts.bez10}</td>
                        <td class="packagescount9">{$color.counts.pak9sa10}</td>
                        <td class="extrakaz9">{$color.counts.dodatnokaz9}</td>
                        <td class="packagescount4">{$color.counts.pak4sa10}</td>
                        <td class="extrakaz4">{$color.counts.dodatnokaz4}</td>
                    </tr>
                {/foreach}
            </table>

        </div>
    </div>


</body>
</html>