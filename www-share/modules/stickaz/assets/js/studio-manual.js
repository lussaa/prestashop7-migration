var Studio = {
    canvasMaxHeight: 600,
    canvasMaxWidth: 800,
    enableImporter: true,
    currentColor: '',
    currentClass: 'transparent',
    mouseDown: false,
    swatches: null,
    canvas: null,
    canvasCtx: null,
    canvasData: [],
    nbCols: null,
    nbRows: null,
    pixelSize: null,
    prevRow: null,
    prevCol: null,
    prevColor: null,
    price3: null,
    price4: null,
    useBrowserCache: false,
    kazWidth: 0,
    kazHeight: 0,
    counts: {},
    colorIndex: 0,
    loading: null,
    getResizedCanvas: function()
    {
        var minX = 10000;
        var maxX = 0;
        var minY = 10000;
        var maxY = 0;

        for (var i=0; i < Studio.nbRows; i++)
        {
            for (var j=0; j < Studio.nbCols; j++)
            {
                if (Studio.canvasData[i][j] != '')
                {
                    minX = j < minX ? j : minX;
                    minY = i < minY ? i : minY;

                    maxX = j > maxX ? j : maxX;
                    maxY = i > maxY ? i : maxY;
                }
            }
        }
        var newCanvasData = [];
        for (var i = minY; i <= maxY; i++)
        {
            newCanvasData.push([]);
            for (var j = minX; j <= maxX; j++)
            {
                newCanvasData[i - minY][j - minX] = Studio.canvasData[i][j];
            }
        }
        if(newCanvasData.length)
        {
            Studio.kazWidth = newCanvasData[0].length;
            Studio.kazHeight = newCanvasData.length;
        }
        return newCanvasData;
    },
    updateCanvas: function()
    {
        Studio.initCanvas(Studio.canvasData[0].length, Studio.canvasData.length, Studio.canvasData);
        Studio.drawCanvas();
    },
    drawLine: function(startX, startY, endX, endY, color, alpha)
    {
        if (typeof alpha == 'undefined')
        {
            alpha = 1;
        }
        Studio.canvasCtx.strokeStyle = color;
        Studio.canvasCtx.globalAlpha = alpha;

        Studio.canvasCtx.beginPath();
        Studio.canvasCtx.moveTo(startX, startY);
        Studio.canvasCtx.lineTo(endX, endY);
        Studio.canvasCtx.closePath();
        Studio.canvasCtx.stroke();
        Studio.canvasCtx.globalAlpha = 1;
    },
    drawPixel: function(col, row, color)
    {
        //console.log(color);
        if (Studio.prevRow && Studio.prevCol && Studio.prevColor)
        {
            if (Studio.prevRow == row && Studio.prevCol == col && Studio.prevColor == color)
            {
                return;
            }
        }

        if (!color)
        {
            if (Studio.currentColor != '')
            {
                Studio.canvasData[col][row] = Studio.currentClass;
                var colorClass = Studio.canvasData[col][row];
                color = $('#' + colorClass).val();
            }
            else
            {
                Studio.canvasData[col][row] = '';
            }
        }

        var newX = 1 + (row * Studio.pixelSize) + row;
        var newY = 1 + (col * Studio.pixelSize) + col;

        if (color)
        {
            Studio.canvasCtx.fillStyle = color;
            //console.log('drawPixel ' + col + '/' + row + ' ' + color);
            Studio.canvasCtx.fillRect(newX, newY, Studio.pixelSize, Studio.pixelSize);
        }
        else
        {
            Studio.canvasCtx.clearRect(newX, newY, Studio.pixelSize, Studio.pixelSize);
        }


        Studio.prevRow = row;
        Studio.prevCol = col;
        Studio.prevColor = color;

        //debug
        //Studio.canvasCtx.fillStyle = 'red';
        //Studio.canvasCtx.fillRect(x, y, 2, 2);
    },
    loopIt: function(f)
    {
        for (var row=0; row < Studio.nbRows; row++)
        {
            for (var col=0; col < Studio.nbCols; col++)
            {
                f(row, col);
            }
        }
    },
    findCenterPixel: function()
    {
        //find the biggest line
        var maxCount = 0;
        var maxCountRow = 0;
        for (var row=0; row < Studio.nbRows; row++)
        {
            var count = 0;
            for (var col=0; col < Studio.nbCols; col++)
            {
                if(Studio.canvasData[row][col] != '')
                {
                    count ++;
                }
            }
            if (count > maxCount)
            {
                maxCount = count;
                maxCountRow = row;
            }
            count = 0;
        }
        //avoid to have the center on an empty pixel
        var centerCol = 0;
        for (var col=Math.floor(Studio.nbCols/2); col < Studio.nbCols; col++)
        {
            if(Studio.canvasData[maxCountRow][col] != '')
            {
                centerCol = col;
                break;
            }
        }
        var half = Studio.pixelSize / 2;
        var startX = centerCol * (Studio.pixelSize + 1) + half;
        var startY =  maxCountRow * (Studio.pixelSize + 1) + half;
        var endX = Studio.width - 150;
        var endY = 50;
	var color = "#E10079";

        Studio.canvasCtx.lineWidth = 2;

        if(!outputAll)
        {
            //center line
            Studio.drawLine(0, startY + half+2, Studio.width ,  startY + half+2, color, 1);

            //arrow from center
            Studio.drawLine(startX, startY, endX , endY, color, 1);
            //end of arrow
            //Studio.drawLine(endX , endY, endX + 200, endY, color, 1);

            //center rect
            Studio.canvasCtx.rect(startX-half + 1, startY-half + 1, Studio.pixelSize , Studio.pixelSize);
            Studio.canvasCtx.closePath();
            Studio.canvasCtx.stroke();

            //text above horizontal arrow drawString or something at (endX, endY - 30) maybe
            Studio.canvasCtx.lineWidth = 1;
            Studio.canvasCtx.font = "14px Helvetica";
            Studio.canvasCtx.fillStyle = color;
        
            Studio.canvasCtx.fillText(startHere, endX + 20, endY - 10);
        }
        
    },
    drawCanvas: function()
    {
        Studio.loopIt
        (
            function(i, j)
            {
                if (Studio.canvasData[i][j] && $('#' + Studio.canvasData[i][j]).size() > 0)
                {
                    Studio.drawPixel(i, j, $('#' + Studio.canvasData[i][j]).val());
                }
            }
        );
        Studio.findCenterPixel();
    },
    initCanvas: function (nbCols, nbRows, canvasData)
    {
        Studio.nbCols = parseInt(nbCols);
        Studio.nbRows = parseInt(nbRows);
        $('#width').val(Studio.nbCols);
        $('#height').val(Studio.nbRows);

        Studio.pixelSize = Math.floor((Studio.canvasMaxHeight - Studio.nbRows) / Studio.nbRows);
        //console.log('initCanvas ' + nbCols + '/' + nbRows + ' pixelSize = ' + Studio.pixelSize);

        Studio.width = (Studio.nbCols * Studio.pixelSize) + Studio.nbCols;
        Studio.height = (Studio.nbRows * Studio.pixelSize) + Studio.nbRows;

        if (Studio.width > Studio.canvasMaxWidth)
        {
            var ratio = Studio.width/ Studio.canvasMaxWidth;
            Studio.pixelSize = Studio.pixelSize / ratio;
            Studio.width = (Studio.nbCols * Studio.pixelSize) + Studio.nbCols + 200;
            Studio.height = (Studio.nbRows * Studio.pixelSize) + Studio.nbRows;
        }

        $('#canvas_container').css('width', Studio.width);

        //console.log('here ' + Studio.width + '/' + Studio.height);

        if (!Studio.canvas)
        {
            Studio.canvas = $('<canvas width="' + Studio.width + 'px" height="' + Studio.height + 'px"></canvas>').appendTo($('#canvas_wrapper'));
        }
        else
        {
            Studio.canvas.get(0).width = Studio.width;
            Studio.canvas.get(0).height = Studio.height;
        }
        $('#canvas_wrapper').css({width: Studio.width, height: Studio.height});

        Studio.canvasCtx = Studio.canvas.get(0).getContext('2d');

        if (typeof canvasData == 'undefined')
        {
            Studio.canvasData = [];
            for (var i=0; i < Studio.nbRows; i++)
            {
                Studio.canvasData.push([]);
                for (var j=0; j < Studio.nbCols; j++)
                {
                    Studio.canvasData[i][j] = '';
                }
            }
        }
        else
        {
            Studio.canvasData = canvasData;
        }
        //console.log(Studio.canvasData);

        // console.log(Studio.pixelSize);
        // console.log(Studio.nbCols);
        // console.log(Studio.nbRows);
        // console.log(Studio.width + 'px');

        if(!outputAll)
        {
            for (var i= Studio.pixelSize + 1; i < Studio.width; i+= (Studio.pixelSize + 1))
            {
                Studio.drawLine(i, 0, i, Studio.height, '#CCC');
            }
            for (var i=Studio.pixelSize + 1; i < Studio.height; i+= (Studio.pixelSize + 1))
            {
                Studio.drawLine(0, i, Studio.width, i, '#CCC');
            }
        }
        Studio.updateCounts();

    },
    getPalette: function()
    {
        var palette = {};
        $('.color_swatch').each(function(){
            palette[$(this).attr('id')] =  {};
            palette[$(this).attr('id')]['c'] = $(this).val();
            var nbr = $('#nb_' + $(this).attr('id')).html();
            palette[$(this).attr('id')]['q'] = nbr.substring(1, nbr.length);
        });
        return palette;
    },
    restoreCanvas: function()
    {
        // Load Data from saved design
        if(typeof currentData != "undefined")
        {
            savedData = $.evalJSON(currentData);
        }
        else
        {
            if(Studio.useBrowserCache)
            {
                var savedData = $.evalJSON(window.localStorage.data);
            }
        }

        if (savedData)
        {
            $('#width').html(savedData.canvas[0].length);
            $('#height').html(savedData.canvas.length);
            for (var i in savedData['palette'])
            {
                $('#' + i).val(savedData['palette'][i]['c']).css('backgroundColor', savedData['palette'][i]['c']);
            }
            Studio.canvasData = savedData.canvas;
            Studio.initCanvas(Studio.canvasData[0].length, Studio.canvasData.length, Studio.canvasData);
            Studio.drawCanvas();
        }
        else
        {
            Studio.initCanvas($('#width').val(), $('#height').val());
        }
    },
    emptyCanvas: function()
    {
        $.msgbox(confirmTrash, {
              type: "confirm",
              buttons : [
                {type: "submit", value: msgYes},
                {type: "cancel", value: msgCancel}
              ]
            }, function(result) {
                if (result)
                {
                    window.localStorage.data = null;
                    Studio.initCanvas(Studio.canvasData[0].length, Studio.canvasData.length);
                }
        });
    },
    initControls: function()
    {
        var controlsData = {};

        $('.color_swatch, .palette_color').each(function(){
            $(this).css('backgroundColor', $(this).val());
        });

        if (!Modernizr.canvas)
        {
            return;
        }

        Studio.swatches = $('.color_swatch');

        $('#pen').click();
    },
    initPricing: function()
    {
        var base = 15;
        var base2 = 8.2;
        var palette = 0.083;
        var factor = 0.75;
        price3 = new Array();
        price3[0] = 0;
        price3[9] = 1.2;

        for(var i = 2; i < 711; i++) // 711 = (80 * 80) / 9
        {
            if(i <= 16)
                { base = base - 0.05 - factor; factor = factor - 0.05; }
            else if (i >= 17 && i <= 50)
                base = base2 = base2 - 0.05;
            else if (i > 50 && i <= 100)
                base = base2 = base2 - 0.025;
            else if (i > 100 && i <= 175)
                base = base2 = base2 - 0.01;
            else if (i > 175 && i <= 200)
                base = 4.5;
            else if (i > 200 && i <= 250)
                base = 4.4;
            else if (i > 250 && i <= 300)
                base = 4.3;
            else if (i > 300 && i <= 350)
                base = 4.2;
            else if (i > 350 && i <= 400)
                base = 4.1;
            else
                base = 4;

            price3[i*9] = palette * i * base;
        }

        price4 = new Array();
        price4[0] = 0;
        price4[4] = 1.1;
        for(var i = 2; i < 1600; i++) // 1600 = (80 * 80) / 4
        {
            price4[i*4] = price3[i*9] * 0.9;
        }

        Studio.price3 = price3;
        Studio.price4 = price4;
    },
    roundPrice: function(price)
    {
        var newPrice = 0;
        newPrice = (Math.round( price * 10) / 10) + 3;

        return newPrice.toFixed(2);
    },
    updateCounts: function()
    {
        var totalPrize9 = 0;
        var totalPrize4 = 0;
        var totalKaz = 0;
        Studio.counts = {};

        Studio.getResizedCanvas();

        for (var i=0; i < Studio.nbRows; i++)
        {
            for (var j=0; j < Studio.nbCols; j++)
            {
                if (Studio.canvasData[i][j] != '')
                {
                    if (typeof Studio.counts[Studio.canvasData[i][j]] == 'undefined')
                    {
                        Studio.counts[Studio.canvasData[i][j]] = 0;
                    }
                    Studio.counts[Studio.canvasData[i][j]]++;
                }
            }
        }

        $('#width1-5').html(Studio.kazWidth * 1.5 + ' <i>('+ (Studio.kazWidth * 1.5 + (Studio.kazWidth/10)) + '*)</i>');
        $('#width2').html(Studio.kazWidth * 2 + ' <i>('+ (Studio.kazWidth * 2 + (Studio.kazWidth/10)) + '*)</i>');
        $('#width3').html(Studio.kazWidth * 3 + ' <i>('+ (Studio.kazWidth * 3 + (Studio.kazWidth/10)) + '*)</i>');
        $('#width4').html(Studio.kazWidth * 4 + ' <i>('+ (Studio.kazWidth * 4 + (Studio.kazWidth/10)) + '*)</i>');
        $('#width5').html(Studio.kazWidth * 5 + ' <i>('+ (Studio.kazWidth * 5 + (Studio.kazWidth/10)) + '*)</i>');

        $('#height1-5').html(Studio.kazHeight * 1.5 + ' <i>('+ (Studio.kazHeight * 1.5 + (Studio.kazHeight/10)) + '*)</i>');
        $('#height2').html(Studio.kazHeight * 2 + ' <i>('+ (Studio.kazHeight * 2 + (Studio.kazHeight/10)) + '*)</i>');
        $('#height3').html(Studio.kazHeight * 3 + ' <i>('+ (Studio.kazHeight * 3 + (Studio.kazHeight/10)) + '*)</i>');
        $('#height4').html(Studio.kazHeight * 4 + ' <i>('+ (Studio.kazHeight * 4 + (Studio.kazHeight/10)) + '*)</i>');
        $('#height5').html(Studio.kazHeight * 5 + ' <i>('+ (Studio.kazHeight * 5 + (Studio.kazHeight/10)) + '*)</i>');

        var i = 0;
        
        
        for(var key in Studio.counts)
        {
            $('#nb_' + key).html('x'+ Studio.counts[key]);
            i++;
            totalKaz += Studio.counts[key];
        }

        Studio.swatches.each(function(index){
            colorID = $(Studio.swatches[index]).attr('id');
            
            if(!Studio.counts[colorID])
            {
                $(Studio.swatches[index]).parent().remove();
            }
            
        });

//console.log(i);
        $('#swatches').width(40 * i +'px');

        $('#total-stickers').html(totalKaz);

        if(totalKaz >= 10)
        {
            $('#1-5cm').html(Studio.roundPrice(totalPrize9*0.55));
            $('#2cm').html(Studio.roundPrice(totalPrize9*0.7));
            $('#3cm').html(Studio.roundPrice(totalPrize9));
            $('#4cm').html(Studio.roundPrice(totalPrize4));
            $('#5cm').html(Studio.roundPrice(totalPrize4*1.25));

            $('#price-info').hide();
            $('#size-prices').show();
        }
        else
        {
            $('#price-info').show();
            $('#size-prices').hide();
            $('#3cm').html(0);
        }
    },
    init: function()
    {
        if (!Modernizr.canvas)
        {
            Studio.initControls();
            $('#not_supported').show();
            $('#controls').css('opacity', 0.5);
            return;
        }

        Studio.initControls();

        Studio.initPricing();

        try
        {
            Studio.restoreCanvas();
        }
        catch(e)
        {
            Studio.initCanvas($('#width').val(), $('#height').val());
        }
    }
};

$(document).ready(function(){
    Studio.init();
});