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

</body>
</html>