

function getStickazSize(kazSize)
{
    var width = parseFloat($('#default-width').html());
    var height = parseFloat($('#default-height').html());
    var defaultSize = parseFloat($('#default-size').html());
    //console.log('Width:', width);
    //console.log('defaultSize:', defaultSize);

    return new Array((width/defaultSize) * kazSize, (height/defaultSize) * kazSize);
}

// // search the combinations' case of attributes and update displaying of availability, prices, ecotax, and image
// function findCombination(size) {
//     // $('#minimal_quantity_wanted_p ').fadeOut();
//     // $('#quantity_wanted').data('pro') || $('#quantity_wanted').val(1);
//
//     //create a temporary 'choice' array containing the choices of the customer
//     var myRadio = $('input[name=stickaz-combination]');
//     var ref = myRadio.filter(':checked').val();
//     //console.log('checkcolor'+ref);
//     var selectedComb = null;
//     //console.log('size'+size);
//     for (stick in stickaz) {
//         if (stickaz[stick][ref]) {
//             for (id in stickaz[stick][ref]['combinations']) {
//                 if (stickaz[stick][ref]['combinations'][id]['size'] == size) {
//                     var selectedComb = stickaz[stick][ref]['combinations'][id]['id'];
//                 }
//             }
//         }
//     }
// }

var width = parseFloat($('#default-width').html());
var height = parseFloat($('#default-height').html());
var defaultSize = parseFloat($('#default-size').html());
//console.log('Width:', width);
//console.log('defaultSize:', defaultSize);
// default product - setting values on the product
// console.log('**************************************************************************************:');
//
// var kazsize = parseFloat($('.size-subelement').html());
// console.log('kazsize:',kazsize);
//
// if ($(".radio2-label").length) {
//
//     $(".radio2-label").each(function () {
//
//         var kazsize = $(this).attr('id').split('-'); //$(find('div.size-subelement'));
//         console.log('kazsize:', kazsize);
//
//         // $(this).click(function() {
//         //     $(".comb-size.selected").each(function() {
//         //         $(this).removeClass('selected');
//         //     });
//         //     $(this).addClass('selected');
//         //     findCombination(idField[0]);
//         // });
//
//         var dimension = getStickazSize(kazsize);
//         var unit = 'cm';
//
//         //if(currencyId == 2)
//         // {
//         //    dimension[0] = Math.round(dimension[0] * 100 * 0.3937) / 100;
//         //    dimension[1] = Math.round(dimension[1] * 100 * 0.3937) / 100;
//         //    unit = 'in';
//         //}
//
//         $(this).find('div.dimension-subelement').html(dimension[0] + ' x ' + dimension[1] + ' ' + unit);
//
//     });
//
//     // $(".comb-size").first().click(); // small hack to tell the application to select the 1.5 size by default
// }
//
