/*
 * Custom code goes here.
 * A template should always ship with an empty custom.js
 */
// default product


function getStickazSize(newSize)
{
    var width = parseFloat($('#default-width').html());
    var height = parseFloat($('#default-height').html());
    var defaultSize = parseFloat($('#default-size').html());

    return new Array((width/defaultSize) * newSize, (height/defaultSize) * newSize);
}
function getPrice(size)
{
    getCombination(size);
    return getTTCPrice(selectedCombination['price']);
}
// search the combinations' case of attributes and update displaying of availability, prices, ecotax, and image
function findCombination(size)
{
    $('#minimal_quantity_wanted_p').fadeOut();
    $('#quantity_wanted').data('pro') || $('#quantity_wanted').val(1);
    //create a temporary 'choice' array containing the choices of the customer
    var myRadio = $('input[name=stickaz-combination]');
    var ref = myRadio.filter(':checked').val();
    //console.log('checkcolor'+ref);
    var selectedComb = null;
    //console.log('size'+size);
    for (stick in stickaz)
    {
        if(stickaz[stick][ref])
        {
            for (id in stickaz[stick][ref]['combinations'])
            {
                if(stickaz[stick][ref]['combinations'][id]['size'] == size)
                {
                    var selectedComb = stickaz[stick][ref]['combinations'][id]['id'];
                }
            }
        }
    }

    // show default product
if($(".comb-size").length)
{
    $(".comb-size").each(function() {

        var idField = $(this).attr('id').split('-');

        $(this).click(function() {
            $(".comb-size.selected").each(function() {
                $(this).removeClass('selected');
            });
            $(this).addClass('selected');
            findCombination(idField[0]);
        });

        var dimension = getStickazSize(idField[1]);
        var unit = 'cm';

        //if(currencyId == 2)
        // {
        //    dimension[0] = Math.round(dimension[0] * 100 * 0.3937) / 100;
        //    dimension[1] = Math.round(dimension[1] * 100 * 0.3937) / 100;
        //    unit = 'in';
        //}

        $(this).find('td.kaz-dimension').html(dimension[0]+' x '+dimension[1]+' '+unit);
        var price = getPrice(idField[0]);
        $(this).find('td.kaz-price').html(price);
    });

    $(".comb-size").first().click(); // small hack to tell the application to select the 1.5 size by default
}

}