<div class="info-top-menu" id="info-top-menu">
    <div class="info-top-menu-inside">
        <button class="link-top info-link" title="{l s='Back to top'}"></button>
        <ul><li><a class="info-link" id="top-box" href="#box">{l s='Boxes'}</a></li><li>
                <a class="info-link" id="top-how-to" href="#how-to">{l s='How it works?'}</a></li><li class="info-collection-studio-link">
                <a class="info-link" id="top-studio" href="#studio">{l s='Collections'} & {l s='Studio'}</a></li><li>
                <a class="info-link" id="top-gallery" href="#gallery">{l s='Gallery'}</a></li>
        </ul>
    </div>
</div>
<div class="info-header-bg" id="info-header">
    <div class="container">
        {*{$HOOK_INFO_SLIDER}*}
        <div class="info-header">
            <div class="info-menu">
                <h1>{l s='Infos'}</h1>
                <ul>
                    <li><a class="info-link" id="info-box" href="#box">{l s='Boxes'}</a></li>
                    <li><a class="info-link" id="info-how-to" href="#how-to">{l s='How it works ?'}</a></li>
                    <li><a class="info-link" id="info-collections" href="#collections">{l s='Collections'}</a></li>
                    <li><a class="info-link" id="info-studio" href="#studio">{l s='Studio'}</a></li>
                    <li><a class="info-link" id="info-gallery" href="#gallery">{l s='Gallery'}</a></li>
                </ul>
            </div>
            <div class="info-header-right">
                <h2>{l s='It all starts with the Kaz'}</h2>
                <div class="info-icons"></div>
                <p>{l s='The Kaz is a square one color sticker that sticks anywhere and is easily repositionable. Stick them one by one to recreate your model.'}
            </div>
        </div>
        <div class="clear"></div>
    </div>
</div>
<div class="container" id="info-content">
    <div class="info-section" id="box">
        <div class="header">
            <div class="corner"></div>
            <h2>{l s='Boxes'}</h2>
        </div>
        <div class="info-box first">
            <h3>{l s='Box'}</h3>
            <p>{l s='For all models over 250 Kaz, contains all your Kaz (and some extra), a manual and setup guide.'}</p>
            <div class="info-stickaz-box"></div>
        </div>
        <div class="info-box">
            <h3>{l s='Mini Box'}</h3>
            <p>{l s='Used for some models having maximum 250 Kaz, contains all your Kaz (and some extra), a manual and setup guide.'}</p>
            <div class="info-stickaz-mini"></div>
        </div>
        <div class="info-box">
            <h3>{l s='Kaz'}</h3>
            <p>{l s='All the models comes with some extra Kaz for each color in case you make a mistake or need to replace them.'}</p>
            <p class="second">{l s='The choice of sizes means that you can design your Stickaz to fit in any space. Pick your desired size between 1.5, 2, 3, 4 or 5 cm squares (that’s 0.6", 0.8", 1.2", 1.6" or 2").'}</p>
            <div class="info-stickaz-kaz"></div>
        </div>
        <div class="clear"></div>
        <div class="info-inside-box">
            <h3>{l s='Inside the boxes'}</h3>
            <p>
	    {l s='Each model comes with a manual and instructions to set up your model.'}<br />
            {l s='See the details in the "How it works" section.'}</p>
            <h4 class="stickaz-box-icon">{l s='Box'}</h4>
            <ul>
                <li><a class="info-inside-box-pic first" href="/themes/stickaz/img/info-inside-big-1.png" rel="info-box"></a></li>
                <li><a class="info-inside-box-pic second" href="/themes/stickaz/img/info-inside-big-2.png" rel="info-box"></a></li>
                <li><a class="info-inside-box-pic third" href="/themes/stickaz/img/info-inside-big-3.png" rel="info-box"></a></li>
            </ul>
            <div class="clear"></div>
            <h4 class="stickaz-mini-icon">{l s='Mini Box'}</h4>
            <ul class="second">
                <li><a class="info-inside-box-pic fourth" href="/modules/infopage/views/img/info-inside-big-4.png" rel="info-box"></a></li>
                <li><a class="info-inside-box-pic fifth" href="/modules/infopage/views/img/info-inside-big-5.png" rel="info-box"></a></li>
                <li><a class="info-inside-box-pic sixth" href="/modules/infopage/views/img/info-inside-big-6.png" rel="info-box"></a></li>
            </ul>
            <div class="clear"></div>
        </div>
    </div>

    <div class="info-section info-how-to" id="how-to">
        <div class="header">
            <div class="corner"></div>
            <h2>{l s='How it works?'}</h2>
        </div>
        <ul>
            <li class="info-how-to-first">
                <div class="img"></div>
                <p>{l s='Stick the first Kaz in the center'}</p>
            </li>
            <li class="info-how-to-second">
                <div class="img"></div>
                <p>{l s='Complete the first line'}</p>
            </li>
            <li class="info-how-to-third">
                <div class="img"></div>
                <p>{l s='Continue line by line'}</p>
            </li>
            <li class="info-how-to-fourth">
                <div class="img"></div>
                <p>{l s='Share your picture'}</p>
            </li>
        </ul>
    </div>

    <div class="info-section section-left" id="collections">
        <div class="header">
            <div class="corner"></div>
            <h2>{l s='Collections'}</h2>
        </div>
        <div class="info-collection-section">
            <a class="img info-stickaz-collection-img" href="{$link->getCategoryLink($stickaz_collection->id_category, $stickaz_collection->link_rewrite)}" title="{l s='Stickaz Collection'}"></a>
            <div class="info-collection-text">
                    <h3 class="stickaz-collection"><a href="{$link->getCategoryLink($stickaz_collection->id_category, $stickaz_collection->link_rewrite)}" title="{l s='Stickaz Collection'}">{l s='Stickaz Collection'}</a></h3>
                <p>{l s='Inspired work from the Stickaz team.'}</p>
                <p>{l s='All the classic pixel-art models.'}</p>
                <p>{l s='Arranged by themes: City, Icon, Monsters, Animals ...'}</p>
            </div>
        </div>
        <div class="info-collection-section">
            <a class="img info-community-collection-img" href="{$link->getCategoryLink($community_collection->id_category, $community_collection->link_rewrite)}" title="{l s='Community Collection'}"></a>
            <div class="info-collection-text">
                <h3 class="community"><a href="{$link->getCategoryLink($community_collection->id_category, $community_collection->link_rewrite)}" title="{l s='Community Collection'}">{l s='Community Collection'}</a></h3>
                <p>{l s='Designs made by the Stickaz members.'}</p>
		<p>{l s='Create your own and share it.'}</p>
                <p>{l s='Browse between more than thousands of models.'}</p>
            </div>
        </div>
    {*</div>*}

    {*<div class="info-section section-right" id="studio">*}
        {*<div class="header">*}
            {*<div class="corner"></div>*}
            {*<h2>{l s='Studio'}</h2>*}
        {*</div>*}
        {*<div class="info-collection-section">*}
            {*<a class="img info-studio-pen" href="{$link->getPageLink('studio.php', false)}"></a>*}
            {*<div class="info-studio-text">*}
                {*<h3 class="studio"><a href="{$link->getPageLink('studio.php', false)}">{l s='Create your model'}</a></h3>*}
                {*<p>{l s='With our online'} <a href="{$link->getPageLink('studio.php', true)}">Studio</a> {l s='editing tool, you can create your very own model Kaz by Kaz. Have fun recreating your favorite characters, logos, monuments and more ... in pixel art.'}</p>*}
            {*</div>*}
        {*</div>*}
        {*<div class="info-collection-section">*}
            {*<a class="img info-studio-preview" href="{$link->getPageLink('studio.php', false)}"></a>*}
            {*<div class="info-studio-text">*}
                {*<h3 class="studio"><a href="{$link->getPageLink('studio.php', false)}">{l s='Share your creations'}</a></h3>*}
                {*<p>{l s='Submit your creations and get them published in the official catalogue and earn a 10% coupon on each sales.'}</p>*}
            {*</div>*}
        {*</div>*}
    {*</div>*}

    <div class="clear"></div>

    <div class="info-section" id="gallery">
        <div class="header">
            <div class="corner"></div>
            <h2>{l s='Gallery'}</h2>
        </div>
        {*{$HOOK_INFO}*}
    </div>
</div>
<script>
    var infoPageRewrite = "{$page.page_name}",
        lang_iso         = "{$language.iso_code}"
</script>
