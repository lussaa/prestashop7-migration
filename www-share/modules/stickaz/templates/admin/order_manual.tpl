<html>
    <head>

        <title>Stickaz Product Manual</title>


        {foreach $my_css as $item}
          <link rel="stylesheet" href="{$item|escape:'html':'UTF-8'}" type="text/css" />
        {/foreach}
        {foreach $my_js as $item}
          <script src="{$item|escape:'html':'UTF-8'}"></script>
        {/foreach}

	</head>
<body>

<h1>Order {$orderId}</h1>

<h2>Addresses</h2>


<ul class="address item invoice">
	<li class="address_title">{l s='Invoice'}</li>
	{if $address_invoice->company}<li class="address_company">{$address_invoice->company|escape:'htmlall':'UTF-8'}</li>{/if}
	<li class="address_name">{$address_invoice->firstname|escape:'htmlall':'UTF-8'} {$address_invoice->lastname|escape:'htmlall':'UTF-8'}</li>
	<li class="address_address1">{$address_invoice->address1|escape:'htmlall':'UTF-8'}</li>
	{if $address_invoice->address2}<li class="address_address2">{$address_invoice->address2|escape:'htmlall':'UTF-8'}</li>{/if}
	<li class="address_city">{$address_invoice->postcode|escape:'htmlall':'UTF-8'} {$address_invoice->city|escape:'htmlall':'UTF-8'}</li>
	<li class="address_country">{$address_invoice->country|escape:'htmlall':'UTF-8'}{if $invoiceState} - {$invoiceState->name|escape:'htmlall':'UTF-8'}{/if}</li>
	{if $address_invoice->phone}<li class="address_phone">{$address_invoice->phone|escape:'htmlall':'UTF-8'}</li>{/if}
	{if $address_invoice->phone_mobile}<li class="address_phone_mobile">{$address_invoice->phone_mobile|escape:'htmlall':'UTF-8'}</li>{/if}
</ul>
<ul class="address alternate_item">
	<li class="address_title">{l s='Delivery'}</li>
	{if $address_delivery->company}<li class="address_company">{$address_delivery->company|escape:'htmlall':'UTF-8'}</li>{/if}
	<li class="address_name">{$address_delivery->firstname|escape:'htmlall':'UTF-8'} {$address_delivery->lastname|escape:'htmlall':'UTF-8'}</li>
	<li class="address_address1">{$address_delivery->address1|escape:'htmlall':'UTF-8'}</li>
	{if $address_delivery->address2}<li class="address_address2">{$address_delivery->address2|escape:'htmlall':'UTF-8'}</li>{/if}
	<li class="address_city">{$address_delivery->postcode|escape:'htmlall':'UTF-8'} {$address_delivery->city|escape:'htmlall':'UTF-8'}</li>
	<li class="address_country">{$address_delivery->country|escape:'htmlall':'UTF-8'}{if $deliveryState} - {$deliveryState->name|escape:'htmlall':'UTF-8'}{/if}</li>
	{if $address_delivery->phone}<li class="address_phone">{$address_delivery->phone|escape:'htmlall':'UTF-8'}</li>{/if}
	{if $address_delivery->phone_mobile}<li class="address_phone_mobile">{$address_delivery->phone_mobile|escape:'htmlall':'UTF-8'}</li>{/if}
</ul>


<h2>Products</h2>
<table id="products">
    <tr>
        <th>Name</th>
        <th>Quantity</th>
		<th>To ship</th>
    </tr>
{foreach $productDetails $pd}
    <tr>
        <td>
            <a href="{$link->getAdminLink('AdminStickazProductManual', true, [], ['id_product' => $pd['product_id'], 'id_lang' => $orderLang])}">
                {$pd['product_name']}
            </a>
        </td>
        <td>
            {$pd['product_quantity']} x
        </td>

		<td>

			<div id="color-shipping">
				<div id="palette-colors">
					<table class="color-list">
						<tr id="header">
							<th class="color">Color name</th>
							<th class="colorcode">Color code</th>
							<th class="colorcount">Broj bez 10%</th>
							<th class="packscount">Paketa od 9 sa10%</th>
							<th class="packscount">Dodatno kaz[9]</th>
							<th class="packscount">Paketa od 4sa10%</th>
							<th class="packscount">Dodatno kaz[4]</th>
						</tr>
						{foreach $pd['shippingColors'] as $color}
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

		</td>



    </tr>
{/foreach}
</table>

</body>