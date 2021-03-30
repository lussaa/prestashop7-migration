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

    {if isset($currentDesign) && $currentDesign}
        var currentData = '{$currentDesign.json}';
    {else}
        var currentData = null;
    {/if}

    var isUserLogged = false;
    var hasUsername = "{$hasUsername}";

    /*
        For translation purpose
    */
    var errorResize = "{l s='Resizing the Studio will erase your current design.\nAre you sure you want to continue?'}";
    var errorStudioSize = "{l s='The Studio designer can not be bigger than 160 pixels width or 160 pixels height'}";
    var confirmPublish = "{l s='You are about to submit your Stickaz to the Kaz Community collection. You will not be able to edit your design afterwards.'}";
    var confirmNew = "{l s='Are you sure you want to create a new model? You will loose your current design.'}";
    var confirmLeaving = "{l s='Are you sure you want to leave the page? You will loose your current design.'}";
    var confirmTrash = "{l s='Are you sure you want to reset the current drawing?'}";
    var confirmDelete = "{l s='Are you sure you want to delete your current design? There will be no come back!'}";
    var msgYes = "{l s='Yes'}";
    var msgCancel = "{l s='Cancel'}";
    var msgContinue = "{l s='Continue'}";
    var msgSave = "{l s='Register my username and my design'}";
    var msgStartHere = "{l s='Start here'}";

    var tip = {
        publish: "{l s='You cannot publish an unsaved design. Please save your design first.'}",
        order: "{l s='You cannot order an unsaved design. Please save your design first.'}",
        reset: "{l s='This will not delete your design (if it\'s already saved) but it will remove all the pixel on the drawing area.'}",
        crop: "{l s='This will remove blank spaces around your design. It does not work if your design is empty. Hotkeys: a'}",
        titleForm: "{l s='Your title cannot contain those characters: [ ] [ ; , . : / ( ) < <  > ; = # { } + \]'}",
        replace: "{l s='Click and select the color you want to replace your current color with. Hotkey: C'}",
        prependColumn : "{l s='Add a column to the left'}",
        appendColumn : "{l s='Add a column to the right'}",
        prependRow : "{l s='Add a row to the top'}",
        appendRow : "{l s='Add a row to the bottom'}",
        draw: "{l s='Hotkey: D or hold ALT down when clicking to perform a \"fill\" action.'}",
        erase: "{l s='Hotkey: E'}",
        transparent: "{l s='Empty / Can\'t be user with drawing tool'}",
    };

    var isMiniStickaz = false;

    // init Manual2
    $(document).ready(function() {
        {if isset($orderSize) && $orderSize}
        Manual2.orderSize = {$orderSize};
        {else}
        Manual2.orderSize = 1.5;
        {/if}
        Manual2.init({$jsonVars.availableColors});
        Manual2.adjustGrid();
        Manual2.autoCrop();
        Manual2.findCenterPixel();
        Manual2.setInfo();
        Manual2.shippingHelpInfo();
    });
</script>

</head>
<body>





<div id="border">
    <div id="border-marge">
        <div id="studio-draw" style="{* if !isset($isMiniStickaz) || !$isMiniStickaz}width: 700px; height: 560px{else}width: 280px; height: 280px{/if *}">
            {if isset($invader) && $invader}
                <div class="invader-logo {if isset($isMiniStickaz) && $isMiniStickaz}mini{/if}"></div>
            {/if}
            <div id="studio-wrapper">
                <div id="canvas_wrapper"></div>
            </div>
            <div id="not_supported">
                <p>
                    {l s='You need a modern browser like Firefox, Chrome or Safari to play with the studio.'}
                </p>
            </div>
        </div>

        <div id="studio-header">
            <div id="palette-colors">
                <ul id="color-list">
                {foreach $availableColors as $color}
                    <li id="c{$color.code}">
                        <div class="color" id="{$color.color}" style="background-color: {$color.color}; border: 1px solid {if $color.color == '#F8F9FB'}#999{else}{$color.color}{/if}"></div>
                        <span style="color: #3A3A3A">--</span>
                    </li>
                {/foreach}
                </ul>
            </div>
        </div>
        <br><br><br><br><br><br><br><br><br><br><br><br>
        <h1>{$currentDesign.name}</h1>

        <div id="studio-header3"> <!--shippingHelpInfo-->
            <div id="palette-colors">
                <table id="color-list">
                    <tr id="header">
                        <th class="color" id="{$color.color}">Color name</th>
                        <th class="colorcode">&nbsp;&nbsp;&nbsp;Color code</th>
                        <th class="colorcount">&nbsp;&nbsp;&nbsp;Broj bez 10%</th>
                        <th class="packscount">&nbsp;&nbsp;&nbsp;Paketa od 9 sa10%</th>
                        <th class="packscount">&nbsp;&nbsp;&nbsp;Dodatno kaz[9]</th>
                        <th class="packscount">&nbsp;&nbsp;&nbsp;Paketa od 4sa10%</th>
                        <th class="packscount">&nbsp;&nbsp;&nbsp;Dodatno kaz[4]</th>
                    </tr>
                    {foreach $availableColors as $color}
                        <tr id="c{$color.code}" class="colorline">
                            <td class="color" id="{$color.color}">{$color.name}</td>
                            <td class="colorcode"></td>
                            <td class="colorcount"></td>
                            <td class="packagescount9"></td>
                            <td class="extrakaz9"></td>
                            <td class="packagescount4"></td>
                            <td class="extrakaz4"></td>

                        </tr>

                    {/foreach}
                </table>

            </div>
        </div   >


        {if isset($invader) && $invader}
            <p class="invader-license">©TAITO CORPORATION 1978, 2014 ALL RIGHTS RESERVED.</p>
        {/if}
    </div>
</div>


</body>
</html>