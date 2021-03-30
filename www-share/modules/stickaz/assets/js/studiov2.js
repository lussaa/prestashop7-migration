var Studio = {
    maxSize : { cols: 160, rows: 160 }, // max size by default
    size: { cols: 20, rows: 20, height: 0, width: 0 }, // contains the number of cols and rows of the grid and the pixel size of the grid
    kazSize : { width: 0, height: 0 }, // contains the number of width and height of the design in KAZ
    colors: [], // contains all the colors
    canvas : { item: null, context: null, data: null}, // contains the canvas object
    previous : { color: '', col: null, row: null },
    current : { color: '', cssClass: 'transparent'},

    // Initiliazes the Studio
    init: function(availableColors,maxCols,maxRows) {

        // Manage browser compatibility here
        if (!Modernizr.canvas)
        {
            $('#not_supported').show();
            return;
        }

        Studio.initUpload();
        Studio.initColors(availableColors);

        if(currentData == null)
        {
            Studio.initCanvas($('#draw-width').val(),$('#draw-height').val());
        }
        else
        {
            Studio.restoreCanvas();
        }

        // crop for the manual to avoid bugs in the pdf rendering
        Studio.autoCrop();

        Studio.initControls(); // must be done at the end
    },
    // Initializes the different tools
    initControls: function() {

        // Manage tool selection
        $('#tools ul.tool-list li').click(function(event){

            if(Studio.getSelectedTool() == 'tool-replace')
            {
                Studio.stopReplaceColorMode();
            }

            $('#tools ul.tool-list li').removeClass('selected');

            switch($(this).attr('id'))
            {
                case 'tool-draw':
                    Studio.setDrawMode();
                    $(this).addClass('selected');
                    break;
                case 'tool-erase':
                    Studio.setEraseMode();
                    $(this).addClass('selected');
                    break;
                case 'tool-crop':
                    Studio.autoCrop();
                    break;
                case 'tool-reset':
                    Studio.emptyCanvas();
                    break;
                case 'tool-replace':
                    Studio.replaceColorMode();
                    $('#tool-replace').addClass('selected');
                    break;
                case 'tool-save':
                case 'tool-saveas':
                    Studio.saveDesign($(this).attr('id'));
                    break;
                case 'tool-publish':
                    Studio.publishDesign(true);
                    break;
                case 'tool-order':
                    Studio.orderDesign(event);
                    break;
                case 'tool-new':
                    Studio.newDesign();
                    break;
                case 'tool-resize':
                    if(Studio.getSelectedTool() != 'tool-draw')
                    {
                        $('#tool-draw').click();
                    }
                    break;
            }
        });

        // Manage hotkeys
        $(document).keyup(function(event){
            if(!$('#tool-resizer input.kaz, #stickaz-title').is(':focus'))
            {
                // a = autocrop
                if(event.keyCode == 65) {
                    $('#tool-crop').click();
                }
                // e = eraser mode
                else if(event.keyCode == 69 || event.keyCode == 48) {
                    $('#tool-erase').click();
                }
                // d = draw mode
                else if(event.keyCode == 68) {
                    $('#tool-draw').click();
                }
                // c = color palette
                else if(event.keyCode == 67) {
                    $('#tool-replace').click();
                }
                else if(event.keyCode == 18) {
                    $('#studio-header #tools ul li#tool-draw').removeClass('fill');
                }
            }
        });

        // Manage the switch between draw tool and fill tool
        $(document).keydown(function(event) {

            if(event.altKey && Studio.getSelectedTool() == 'tool-draw')
            {
                $('#studio-header #tools ul li#tool-draw').addClass('fill');
            }
        });

        // Manage information tooltips
        var shared = {
           style: { classes: 'ui-tooltip-dark'},
           position: {
              my: 'center left',
              at: 'center right',
           }
        };

        if(!currentData)
        {
            $('li#tool-publish').qtip({
                style: { classes: 'ui-tooltip-dark'},
                position: {
                   my: 'bottom left',
                   at: 'top center',
                },
               content: tip.publish
            });
            $('li#tool-order').qtip({
                style: { classes: 'ui-tooltip-dark'},
                position: {
                   my: 'bottom left',
                   at: 'top center',
                },
                content: tip.order
            });
        }

        if(!isUserLogged)
        {
            $('li#tool-save').qtip({
                style: { classes: 'ui-tooltip-dark'},
                position: {
                   my: 'bottom left',
                   at: 'top center',
                },
                content: tip.saveNotLogged
            });
        }

        $('li#tool-reset').qtip($.extend({}, shared, {
            content: tip.reset
        }));
        $('li#tool-crop').qtip($.extend({}, shared, {
            content: tip.crop
        }));
        $('input#stickaz-title').qtip($.extend({}, shared, {
            content: tip.titleForm
        }));
        $('li#tool-replace').qtip($.extend({}, shared, {
            content: tip.replace
        }));
        $('li#tool-draw').qtip($.extend({}, shared, {
            content: tip.draw
        }));
        $('li#tool-erase').qtip($.extend({}, shared, {
            content: tip.erase
        }));


        $('li#tool-resize td.top').qtip({
            style: { classes: 'ui-tooltip-dark'},
            position: {
               my: 'bottom left',
               at: 'top right',
            },
            content: tip.prependRow
        });
        $('li#tool-resize td.left').qtip({
            style: { classes: 'ui-tooltip-dark'},
            position: {
               my: 'center right',
               at: 'center left',
            },
            content: tip.prependColumn
        });
        $('li#tool-resize td.right').qtip($.extend({}, shared, {
            content: tip.appendColumn
        }));
        $('li#tool-resize td.bottom').qtip({
            style: { classes: 'ui-tooltip-dark'},
            position: {
               my: 'top left',
               at: 'bottom right',
            },
            content: tip.appendRow
        });

        // Manage color selection
        $('ul#color-list li').live('click', function() {

            if($(this).attr('id') == 'transparent')
            {
                return;
            }

            $('ul#color-list li.selected').removeClass('selected');
            $(this).addClass('selected');

            Studio.setDrawMode();

            // We select the draw tool if it not selected yet
            if(!$('#tools ul.tool-list li#tool-draw').hasClass('selected'))
            {
                $('#tools ul.tool-list li#tool-draw').click();
            }
        });

        // Manage draw events
        Studio.canvas.item.mousedown(function(event){
            Studio.fillPixel(event);
            $(this).mousemove(Studio.fillPixel);

        })
        .mouseup(function(event){
            event.stopPropagation(); // prevent the document.mouseup to be executed
            Studio.saveCanvas();
            $(this).unbind('mousemove');
        });

        // Prevents bug if the user goes out the drawing area, mouse up and comes back in the drawing area
        $(document).mouseup(function() {
            Studio.saveCanvas();
            Studio.canvas.item.unbind('mousemove');
        });

        Studio.canvas.item.mousedown(function() {
            if(Studio.getSelectedTool() == 'tool-replace')
            {
                $('#tools ul.tool-list li#tool-draw').click();
            }
        });

        // Manage resizer tool
        $('#grid-manager .top').hover(function() {
               $('#grid-manager .top-left').addClass('selected');
               $(this).addClass('selected');
               $('#grid-manager .top-right').addClass('selected');
           }, function() {
               $('#grid-manager .top-left').removeClass('selected');
               $(this).removeClass('selected');
               $('#grid-manager .top-right').removeClass('selected');
           });

           $('#grid-manager .left').hover(function() {
               $('#grid-manager .top-left').addClass('selected');
               $(this).addClass('selected');
               $('#grid-manager .bottom-left').addClass('selected');
           }, function() {
               $('#grid-manager .top-left').removeClass('selected');
               $(this).removeClass('selected');
               $('#grid-manager .bottom-left').removeClass('selected');
           });

           $('#grid-manager .right').hover(function() {
               $('#grid-manager .top-right').addClass('selected');
               $(this).addClass('selected');
               $('#grid-manager .bottom-right').addClass('selected');
           }, function() {
               $('#grid-manager .top-right').removeClass('selected');
               $(this).removeClass('selected');
               $('#grid-manager .bottom-right').removeClass('selected');
           });

           $('#grid-manager .bottom').hover(function() {
               $('#grid-manager .bottom-left').addClass('selected');
               $(this).addClass('selected');
               $('#grid-manager .bottom-right').addClass('selected');
           }, function() {
               $('#grid-manager .bottom-left').removeClass('selected');
               $(this).removeClass('selected');
               $('#grid-manager .bottom-right').removeClass('selected');
           });

        // Manage resizing events
        $('#grid-manager .top').click(Studio.prependRow);
        $('#grid-manager .left').click(Studio.prependColumn);
        $('#grid-manager .right').click(Studio.appendColumn);
        $('#grid-manager .bottom').click(Studio.appendRow);
        $('input#resize-draw-area').click(Studio.resizeDrawingArea);

        // Manage confirmation message when leaving the page with unsaved work
        var initialJSON = $("input[name='json-code']").val();

        $('a').each(function(){

            if(!$(this).hasClass('product-delete')
                && !$(this).hasClass('product-publish')
                && !$(this).hasClass('adjust_size')
                && !($(this).attr('id') == 'resize-draw-area')
                && !($(this).attr('id') == 'tool-order')
                || (currentData && $(this).attr('id') == 'tool-order')
               )
            {

               $(this).click(function(event) {

                   currentJSON = $("input[name='json-code']").val();

                   if(initialJSON != currentJSON)
                   {
                       event.preventDefault();
                       var url = $(this).attr('href');
                       $.msgbox(confirmLeaving, {
                             type: "confirm",
                             buttons : [
                               {type: "submit", value: msgContinue},
                               {type: "cancel", value: msgCancel}
                             ]
                           }, function(result) {
                               if(result)
                               {
                                   window.location.href = url;
                               }
                           });
                     }
               });
            }
        });

        // Select the default color (it will also select the draw tool by default)
        $('ul#color-list li').first().click();
    },
    // Initializes all stuff related to colors
    initColors: function(availableColors) {
        Studio.colors = availableColors;
        // init color tooltips
        for(var i=0; i<Studio.colors.length; i++)
        {
            $('ul#color-list li#c'+Studio.colors[i].code).qtip({
                style: { classes: 'ui-tooltip-dark'},
                position: {
                   my: 'bottom left',
                   at: 'top center',
                },
                content: Studio.colors[i].name
            });
        }

        $('ul#color-list li#transparent').qtip({
            style: { classes: 'ui-tooltip-dark'},
            position: {
               my: 'bottom right',
               at: 'top center',
            },
            content: tip['transparent']
        });
    },
    // Initiliazes the drawing area
    initCanvas: function (nbCols, nbRows, canvasData)
    {
        nbCols = parseInt(nbCols);
        nbRows = parseInt(nbRows);

        // if the input size is too big or incoherent, we set the maximum size insted
        if(nbCols < 0 || nbCols > Studio.maxSize.cols || nbRows < 0 || nbRows > Studio.maxSize.rows)
        {
            Studio.size.cols = Studio.maxSize.cols;
            Studio.size.rows = Studio.maxSize.rows;
        }
        else
        {
            Studio.size.cols = nbCols;
            Studio.size.rows = nbRows;
        }

        $('#draw-width').val(Studio.size.cols);
        $('#draw-height').val(Studio.size.rows);

        Studio.pixelSize = Math.floor((Studio.getMaxWidth() - Studio.size.cols) / Studio.size.cols);
        Studio.size.width = (Studio.size.cols * Studio.getPixelSize()) + Studio.size.cols;
        Studio.size.height = (Studio.size.rows * Studio.getPixelSize()) + Studio.size.rows;
        var studioWidth = Studio.size.width;
        var studioHeight = Studio.size.height;

        $('#canvas_wrapper').addClass('transparent_tile');

        if (!Studio.canvas.item)
        {
            Studio.canvas.item = $('<canvas width="' + studioWidth + 'px" height="' + studioHeight + 'px"></canvas>').appendTo($('#canvas_wrapper'));
        }
        else
        {
            Studio.canvas.item.get(0).width = studioWidth;
            Studio.canvas.item.get(0).height = studioHeight;
        }

        $('#canvas_wrapper').css({width: studioWidth, height: studioHeight});
        $('#studio-wrapper').css({width: studioWidth, height: studioHeight}); // for margin

        Studio.canvas.context = Studio.canvas.item.get(0).getContext('2d');

        if (typeof canvasData == 'undefined')
        {
            Studio.canvas.data = [];
            for (var i=0; i < Studio.size.rows; i++)
            {
                Studio.canvas.data.push([]);
                for (var j=0; j < Studio.size.cols; j++)
                {
                    Studio.canvas.data[i][j] = '';
                }
            }
        }
        else
        {
            Studio.canvas.data = canvasData;
        }

        Studio.drawGrid();
        Studio.updateCounts();
        Studio.saveCanvas();
    },
    // Returns the maximum width of the drawing area
    getMaxWidth: function() {
        return $('#studio-draw').width();
    },
    // Returns the pixel size
    getPixelSize: function() {
        return Math.floor((Studio.getMaxWidth() - Studio.size.cols) / Studio.size.cols);
    },
    // Returns the selected tool
    getSelectedTool: function() {
        return $('#tools ul.tool-list li.selected').attr('id');
    },
    // Draw the grid
    drawGrid: function()
    {
        // Draw grid
        for (var i=0; i < Studio.size.width + Studio.size.rows; i+= (Studio.getPixelSize() + 1))
        {
            Studio.drawLine(i, 0, i, Studio.size.height);
        }
        for (var i=0; i < Studio.size.height + Studio.size.rows; i+= (Studio.getPixelSize() + 1))
        {
            Studio.drawLine(0, i, Studio.size.width, i);
        }
    },
    roundPrice: function(price)
    {
        var newPrice = 0;
        newPrice = (Math.round( price * 10) / 10);

        return newPrice.toFixed(2);
    },
    getPrice: function(size, kaz)
    {
        var priceKaz1 = 0.0025;
        var priceKaz2 = 0.0045;
        var priceKaz3 = 0.007;
        var priceKaz4 = 0.013;
        var priceKaz5 = 0.019;

        var costKaz1 = 2.5;
        var costKaz2 = 2.5;
        var costKaz3 = 2.8;
        var costKaz4 = 2.8;
        var costKaz5 = 2.8;

        var fact = 0.43;
        var price = 0;

        //round up to 5
        kaz = (Math.ceil( kaz / 5) * 5 );
        if(kaz <= 100)
        {
            var costKaz1 = 1.5;
            var costKaz2 = 1.5;
            var costKaz3 = 1.5;
            var costKaz4 = 1.5;
            var costKaz5 = 1.5;
        }

        if(kaz <= 95)
        {
            fact = fact - (kaz/5/100);
        }
        else if(kaz == 100)
        {
            fact = 0.235;
        }
        else if(kaz == 105)
        {
            fact = 0.345;
        }
        else if(kaz == 110)
        {
            fact = 0.336;
        }
        else if(kaz == 115)
        {
            fact = 0.326;
        }
        else if(kaz > 115 && kaz <= 400)
        {
            fact = 0.326;
            fact = fact - ((kaz - 115) / 5) * 0.002;
        }
        else if(kaz > 400 && kaz < 1000)
        {
            fact = 0.2177;
            fact = fact - ((kaz - 115) / 5) / 10000;
        }
        else if(kaz > 1000)
        {
            fact = 0.20;
        }

        if(kaz == 105 && size == 5)
        {
            fact = 0.332;
        }

        switch(size)
        {
            case 1.5:
                var price = (((kaz * priceKaz1) + costKaz1) / fact) * 1.196;
                break;
            case 2:
                var price = (((kaz * priceKaz2) + costKaz2) / fact)  * 1.196;
                break;
            case 3:
                var price = (((kaz * priceKaz3) + costKaz3) / fact) * 1.196;
                break;
            case 4:
                var price = (((kaz * priceKaz4) + costKaz4) / fact) * 1.196;
                break;
            case 5:
                var price = (((kaz * priceKaz5) + costKaz5) / fact) * 1.196;
                break;
        }
        return price;
    },
    initUpload: function()
    {
        $('.upload').fileUploadUI({
            uploadTable: $('.upload_files'),
            downloadTable: $('.download_files'),
            buildUploadRow: function (files, index) {
                var file = files[index];
                return $(
                    '<tr>' +
                    '<td class="file_upload_progress"><div><\/div><\/td>' +
                    '<td class="file_upload_cancel">' +
                    '<div class="ui-state-default ui-corner-all ui-state-hover" title="Cancel">' +
                    '<span class="ui-icon ui-icon-cancel">Cancel<\/span>' +
                    '<\/div>' +
                    '<\/td>' +
                    '<\/tr>'
                );
            },
            buildDownloadRow: function (file) {
                $('#background_image_size').html('');
                $('#background_image_thumb').hide().removeClass('mini').attr('src', '/img/studio_importer/' + file.name).load(function(){Studio.useBackgroundImage(true);});
            }
        });
    },
    // Set the current color
    setDrawMode: function()
    {
        var colorElement = $('ul#color-list li.selected');

        if(!colorElement) // An error occured
        {
            //console.log('An error occured during color selection.');
            return false;
        }

        Studio.current.color = colorElement.find('div.color').css('background-color');
        Studio.current.cssClass = colorElement.attr('id');
    },
    // Set the current color to '' to work as an eraser
    setEraseMode: function()
    {
        Studio.current.color = '';
        Studio.current.cssClass = 'transparent';
    },
    // Crop the current canvas to match the size in KAZ
    autoCrop: function()
    {
            var newCanvasData = Studio.getResizedCanvas();

            // avoid triggering an error if the current canvas is empty
            if(typeof(newCanvasData) != 'undefined' && typeof(newCanvasData[0]) != 'undefined')
            {
                Studio.initCanvas(newCanvasData[0].length, newCanvasData.length, newCanvasData);
                Studio.drawCanvas();
            }

            $('#tools ul.tool-list li#tool-draw').click();
    },
    // Determines the (X,Y) of the pixel to draw and call the pixel drawer
    fillPixel: function(event)
    {
        if ((event.type == 'mousedown' || event.type=='mousemove'))
        {
            var x = event.pageX - event.target.offsetLeft || event.offsetX;
            var y = event.pageY - event.target.offsetTop || event.offsetY;

            var col = Math.floor(x/ (Studio.getPixelSize() + 1));
            var row = Math.floor(y/(Studio.getPixelSize() + 1));

            // we make the fill only on click, otherwise it would cause lags
            if(event.altKey && event.type!='mousemove')
            {
                Studio.drawSetofPixelsRecurs(Studio.canvas.data[row][col], col, row);
            }
            else
            {
                Studio.drawPixel(row, col);
            }
        }
    },
    // Uses the canvas to draw lines
    drawLine: function(startX, startY, endX, endY, color, lineWidth)
    {
        Studio.canvas.context.beginPath();
        Studio.canvas.context.moveTo(startX, startY);
        Studio.canvas.context.lineTo(endX, endY);
        Studio.canvas.context.closePath();

        if(typeof(color) == 'undefined')
        {
            color = '#DFDFDF';
        }

        if(typeof(lineWidth) == 'undefined')
        {
            lineWidth = 0.5;
        }

        Studio.canvas.context.strokeStyle = color;
        Studio.canvas.context.lineWidth = lineWidth;
        Studio.canvas.context.stroke();
        Studio.canvas.context.closePath();
    },
    // Uses the canvas to draw a pixel
    drawPixel: function(col, row, color)
    {
        // if we click a case with the same color then we don't need to draw it again.
        if (Studio.previous.row && Studio.previous.col && Studio.previous.color)
        {
            if (Studio.previous.row == row && Studio.previous.col == col && Studio.previous.color == color)
            {
                return;
            }
        }

        if (!color)
        {
            if (Studio.current.color != '')
            {
                if(typeof(Studio.canvas.data[col]) == 'undefined')
                {
                    Studio.canvas.data[col] = [];
                }

                Studio.canvas.data[col][row] = Studio.current.cssClass;
                color = Studio.current.color;
            }
            else
            {
                Studio.canvas.data[col][row] = ''; // erase the model case
            }
        }

        var newX = 1 + (row * Studio.getPixelSize()) + row;
        var newY = 1 + (col * Studio.getPixelSize()) + col;

        if (color)
        {
            Studio.canvas.context.fillStyle = color;
            Studio.canvas.context.fillRect(newX, newY, Studio.pixelSize, Studio.pixelSize);
        }
        else
        {
            Studio.canvas.context.clearRect(newX, newY, Studio.getPixelSize(), Studio.getPixelSize()); // erase the view case
        }

        // set history
        Studio.previous.row = row;
        Studio.previous.col = col;
        Studio.previous.color = color;
    },
    // Fill all the color of a specified color (base) in an another color (target)
    switchColors: function(baseColorCode, targetColorCode)
    {
        if(baseColorCode != targetColorCode) // optimization, prevent useless loop
        {
            Studio.loopIt
            (
                function(i, j)
                {
                    if(Studio.canvas.data[i][j] == baseColorCode)
                    {
                        if(targetColorCode == 'transparent')
                        {
                            Studio.setEraseMode();
                            Studio.drawPixel(i, j);
                        }
                        else
                        {
                            Studio.canvas.data[i][j] = targetColorCode;
                            Studio.drawPixel(i, j, $('#' + Studio.canvas.data[i][j] + " div.color").css('background-color'));
                        }
                    }
                }
            );
        }
    },
    // draw pixels around a specified pixel (if the case have the right color)
    drawSetofPixelsRecurs: function(color, col, row)
    {
        var data = Studio.canvas.data;

        if(Studio.current.cssClass == color || (Studio.current.cssClass == 'transparent' && color == '')) // optimization, prevent useless recursion and browser crash
        {
            return false;
        }

        Studio.drawPixel(row, col);

        if(data[row-1] != null && data[row-1][col] == color)
        {
            Studio.drawSetofPixelsRecurs(color,col,row-1);
        }

        if(data[row+1] != null && data[row+1][col] == color)
        {
            Studio.drawSetofPixelsRecurs(color,col,row+1);
        }

        if(data[row][col-1] != null && data[row][col-1] == color)
        {
            Studio.drawSetofPixelsRecurs(color,col-1,row);
        }

        if(data[row][col+1] != null && data[row][col+1] == color)
        {
            Studio.drawSetofPixelsRecurs(color,col+1,row);
        }
    },
    // Starts the replace color mode
    replaceColorMode: function() {

        $('ul#color-list li div.color').addClass('isPaletteSelection');

        $('ul#color-list li').click(function() {

            baseColorCode = Studio.current.cssClass;
            targetColorCode = $(this).attr('id');
            Studio.switchColors(baseColorCode, targetColorCode);
            Studio.updateCounts();
            Studio.stopReplaceColorMode();
        });
    },
    // Stops the replace color mode
    stopReplaceColorMode: function() {
        $('ul#color-list li').unbind('click');
        $('ul#color-list li div.color').removeClass('isPaletteSelection');
    },
    // Update the canvas
    updateCanvas: function()
    {
        Studio.initCanvas(Studio.canvas.data[0].length, Studio.canvas.data.length, Studio.canvas.data);
        Studio.drawCanvas();
    },
    // Draw the canvas width model values
    drawCanvas: function()
    {
        Studio.loopIt
        (
            function(i, j)
            {
                if (Studio.canvas.data[i] != null && Studio.canvas.data[i][j] && $('#' + Studio.canvas.data[i][j]  + " div.color").css('background-color') != '')
                {
                    Studio.drawPixel(i, j, $('#' + Studio.canvas.data[i][j] + " div.color").css('background-color'));
                }
            }
        );
    },
    // Applies a function to each drawn pixel
    loopIt: function(f)
    {
        for (var row=0; row < Studio.size.rows; row++)
        {
            for (var col=0; col < Studio.size.cols; col++)
            {
                f(row, col);
            }
        }
    },
    // Adds a col to the left
    prependColumn:function()
     {
         if(Studio.size.cols == Studio.maxSize.cols)
         {
             $.msgbox(errorStudioSize, {type: "error"});
             return;
         }

         for(i in Studio.canvas.data)
         {
             Studio.canvas.data[i].unshift('');
         }
         Studio.updateCanvas();
     },
     // Adds a col to the right
     appendColumn:function()
     {
         if(Studio.size.cols == Studio.maxSize.cols)
         {
             $.msgbox(errorStudioSize, {type: "error"});
             return;
         }

         for(i in Studio.canvas.data)
         {
             Studio.canvas.data[i].push('');
         }
         Studio.updateCanvas();
     },
     // Adds a row to the top
     prependRow:function()
     {
         if(Studio.size.rows == Studio.maxSize.rows)
         {
             $.msgbox(errorStudioSize, {type: "error"});
             return;
         }

         var newRow = [];
         for(var i=0; i<Studio.canvas.data[0].length; i++)
         {
             newRow.push('');
         }
         Studio.canvas.data.unshift(newRow);
         Studio.updateCanvas();
     },
     // Adds a row to the bottom
     appendRow:function()
     {
         if(Studio.size.rows == Studio.maxSize.rows)
         {
             $.msgbox(errorStudioSize, {type: "error"});
             return;
         }

         var newRow = [];
         for(var i=0; i<Studio.canvas.data[0].length; i++)
         {
             newRow.push('');
         }
         Studio.canvas.data.push(newRow);
         Studio.updateCanvas();
     },
     // Resizes the drawing area
     resizeDrawingArea: function()
     {
         var width = $('#draw-width').val();
         var height = $('#draw-height').val();
         if ((width > 0 && width <= Studio.maxSize.cols) && height > 0 &&  height <= Studio.maxSize.rows)
         {
             $.msgbox(errorResize, {
                   type: "confirm",
                   buttons : [
                     {type: "submit", value: msgYes},
                     {type: "cancel", value: msgCancel}
                   ]
                 }, function(result) {
                     if (result)
                     {
                         Studio.initCanvas(width, height);
                     }
             });
         }
         else
         {
             $.msgbox(errorStudioSize, {type: "error"});
         }
     },
     // Returns the JSON of the palette
     getPaletteJSON: function()
     {
         var palette = {};
         $('ul#color-list li').each(function(){
             if($(this).attr('id') != 'transparent')
             {
                 palette[$(this).attr('id')] =  {};
                 palette[$(this).attr('id')]['c'] = Studio.rgb2hex($(this).find('div.color').css('background-color'));
                 var nbr = $(this).find('span').html();

                 if(nbr != '--')
                 {
                     palette[$(this).attr('id')]['q'] = parseInt(nbr);
                 }
                 else
                 {
                     palette[$(this).attr('id')]['q'] = 0;
                 }
             }
         });
         return palette;
     },
     // Returns the JSON of the design
     getDataJSON: function()
     {
         return {
             'palette' : Studio.getPaletteJSON(),
             'size': {'width': Studio.size.cols, 'height': Studio.size.rows },
             'canvas' : Studio.canvas.data,
             'sizePX' : { 'width': Studio.size.width, 'height': Studio.size.height },
         };
     },
     // Clean the current drawing area
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
                     Studio.initCanvas(Studio.canvas.data[0].length, Studio.canvas.data.length);
                 }

                 $('#tools ul.tool-list li#tool-draw').click(); // we select back the draw tool
         });
     },
     // Updates the information table (prices and dimensions)
     updateCounts: function()
     {
         var totalPrice9 = 0;
         var totalPrice4 = 0;
         var totalKaz = 0;
         Studio.counts = {};

         Studio.getResizedCanvas(); // Sets the size of the design in KAZ

         for (var i=0; i < Studio.size.rows; i++)
         {
             for (var j=0; j < Studio.size.cols; j++)
             {
                 if (Studio.canvas.data[i] != 'undefined' && Studio.canvas.data[i] != null && Studio.canvas.data[i][j] != '')
                 {
                     if (typeof Studio.counts[Studio.canvas.data[i][j]] == 'undefined')
                     {
                         Studio.counts[Studio.canvas.data[i][j]] = 0;
                     }
                     Studio.counts[Studio.canvas.data[i][j]]++;
                 }
             }
         }

         // Sets the number of Kaz for each colors
         var nbColor;
         var idColor;

         $('ul#color-list li').each(function() {
             idColor = $(this).attr('id');
             nbColor = isNaN(Studio.counts[idColor]) ? 0 : Studio.counts[idColor];
             if(nbColor == 0)
             {
                 $(this).find('span').html('--');
             }
             else
             {
                $(this).find('span').html(nbColor);
             }

            totalKaz += nbColor;
         });

         if(totalKaz >= 25)
         {
             $('#stickaz-info table').show();
             $('#stickaz-info div.information').hide();

             var id;
             var finalTotalPrice;
             $('#stickaz-info table tr').each(function() {

                 id = parseFloat($(this).attr('id'));

                 if(!isNaN(id)) // the first row is the header
                 {
                     $(this).find('td.dimension span').html(Studio.kazSize.width * id + ' x ' + Studio.kazSize.height * id);
                     finalTotalPrice = Studio.roundPrice(Studio.getPrice(id, totalKaz));

                     $(this).find('td.draw-price span').html(finalTotalPrice);
                 }

             });
         }
         else
         {
             $('#stickaz-info table').hide();
             $('#stickaz-info div.information').show();
         }

         // Sets the total number of Kaz
         $('#stickaz-info div.title span').html(totalKaz);

     },
     // Gets the resized canvas to match the design. It also sets the kazSize attributes.
     getResizedCanvas: function()
     {
         var minX = 10000;
         var maxX = 0;
         var minY = 10000;
         var maxY = 0;

         for (var i=0; i < Studio.size.rows; i++)
         {
             for (var j=0; j < Studio.size.cols; j++)
             {
                 if (Studio.canvas.data[i] != 'undefined' && Studio.canvas.data[i] != null && Studio.canvas.data[i][j] != '')
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
                 if(Studio.canvas.data[i] != 'undefined' && Studio.canvas.data[i] != null)
                 {
                     newCanvasData[i - minY][j - minX] = Studio.canvas.data[i][j];
                 }
                 else {
                     newCanvasData[i - minY][j - minX] = '';
                 }
             }
         }
         if(newCanvasData.length)
         {
             Studio.kazSize.width = newCanvasData[0].length;
             Studio.kazSize.height = newCanvasData.length;
         }
         return newCanvasData;
     },
     // Serialize the javascript into JSON in the appropriate form
     saveCanvas: function()
     {
         Studio.updateCounts(); // must be executed first

         var json = $.toJSON(Studio.getDataJSON());
         $('input[name="json-code"]').val(json);
     },
     // Loads data from saved design
     restoreCanvas: function()
     {
         savedData = $.evalJSON(currentData);
         if (savedData)
         {
             $('#draw-width').val(savedData.canvas[0].length);
             $('#draw-height').val(savedData.canvas.length);

             Studio.canvas.data = savedData.canvas;
             Studio.initCanvas(Studio.canvas.data[0].length, Studio.canvas.data.length, Studio.canvas.data);
             Studio.saveCanvas();
             Studio.drawCanvas();
         }
     },
      // Pixelize an uploaded image and displays it in the Studio
      useBackgroundImage: function(useOriginalImageSize)
      {
          if (typeof useOriginalImageSize == 'undefined')
          {
              var useOriginalImageSize = false;
          }

          var realWidth = $('#background_image_thumb').width();
          var realHeight = $('#background_image_thumb').height();
          var img = $('#background_image_thumb').get(0);

          var maxWidth = 100;
          if (useOriginalImageSize)
          {
              if (realWidth > maxWidth)
              {
                  while (realWidth > maxWidth)
                  {
                      realWidth = Math.ceil(realWidth / 2);
                      realHeight = Math.ceil(realHeight / 2);
                  }
              }

              Studio.initCanvas(realWidth, realHeight);
          }
          else
          {
              Studio.initCanvas(Studio.size.cols, Studio.size.rows);
          }

          Studio.canvas.context.drawImage(img,0,0,Studio.size.width,Studio.size.height);

          Studio.colorDict = {};
          Studio.counts = {};
          Studio.colorIndex = 0;

          Studio.loopIt
          (
              function(row, col)
              {
                  var pixelColor = Studio.getPixelFromBackgroundImage(col, row);

                  if (pixelColor == 'transparent')
                  {
                      Studio.setEraseMode();
                      Studio.drawPixel(row, col);
                  }
                  else
                  {
                      var color = Studio.findClosestColor(pixelColor);
                      Studio.setDrawMode(); // set the selected color by default

                      Studio.canvas.data[row][col] = $('div.color#\\'+color).parent().attr('id');
                  }
              }
          );

          Studio.canvas.context.clearRect(0,0,Studio.size.width,Studio.size.height);
          if (useOriginalImageSize)
          {
              // deprecated: Studio.cleanUselessWhites();
              Studio.autoCrop();
          }

          Studio.saveCanvas();
          Studio.drawCanvas();
      },
      // Returns if a pixel is transparent or not
      isPixelTransparent: function(row, col)
      {
          if (typeof Studio.canvas.data[row] != 'undefined' && typeof Studio.canvas.data[row][col] != 'undefined')
          {
              return Studio.canvas.data[row][col] == '' || Studio.isSamePixel(Studio.getColorAt(row, col), [255, 255, 255]);
          }
          return false;
      },
      // Clean pixels around the design
      cleanUselessWhites: function()
      {
           Studio.setEraseMode();
          for (var row = 0; row < Studio.size.rows; row ++)
          {
              var col = 0;
              while (Studio.isPixelTransparent(row, col))
              {
                  Studio.drawPixel(row, col);
                  col ++;
              }
              var col = Studio.size.cols - 1;
              while (Studio.isPixelTransparent(row, col))
              {
                  Studio.drawPixel(row, col);
                  col --;
              }
          }
          for (var col = 0; col < Studio.size.cols; col ++)
          {
              var row = 0;
              while (Studio.isPixelTransparent(row, col))
              {
                  Studio.drawPixel(row, col);
                  row ++;
              }
              var row = Studio.size.rows - 1;
              while (Studio.isPixelTransparent(row, col))
              {
                  Studio.drawPixel(row, col);
                  row --;
              }
          }

      },
      // Returns if a two colors can be considered as the same
      isSamePixel: function(originPixel, otherPixel, tolerance)
      {
          if (typeof tolerance == 'undefined')
          {
              var tolerance = 50;
          }
          var match = (Math.abs(originPixel[0] - otherPixel[0])
              + Math.abs(originPixel[1] - otherPixel[1])
              + Math.abs(originPixel[2] - otherPixel[2])) / 3;
          if (match <= tolerance)
          {
              return match;
          }
          return false;
      },
      // Returns the closest color in our palette for a given color
      findClosestColor: function(color)
      {
          var serialized = color.join('.');
          if (Studio.colorDict[serialized])
          {
              return Studio.colorDict[serialized].colorData.color;
          }
          else
          {
              for (var i=0, scl = Studio.colors.length;i<scl;i++)
              {
                  var c = Studio.hex2rgb(Studio.colors[i].color);
                  var match = Studio.isSamePixel(color, c);
                  if (match)
                  {
                      if (!Studio.colorDict[serialized] || match < Studio.colorDict[serialized].match)
                      {
                          Studio.colorDict[serialized] = {match: match, colorData: Studio.colors[i], colorRgb: c};
                      }
                  }
              }
              if (Studio.colorDict[serialized])
              {
                  return Studio.colorDict[serialized].colorData.color;
              }
          }
          return '#FF0000';
      },
      // Retrieves color data from an image at a specific position
      getPixelFromBackgroundImage: function(col, row)
      {
          var half = Studio.getPixelSize() / 2;
          var x = (col * (Studio.getPixelSize() + 1)) + half;
          var y = (row * (Studio.getPixelSize() + 1)) + half;
          var imageData = Studio.canvas.context.getImageData(x, y , 1, 1);

          if (imageData.data[3] == 0)
          {
              return 'transparent';
          }
          return [imageData.data[0], imageData.data[1], imageData.data[2]];
      },
      // Retrieves the color of a specific position
      getColorAt: function(row, col)
      {
          return Studio.hex2rgb($('li#' + Studio.canvas.data[row][col] + ' div.color').css('background-color'));
      },
      // Transform an hexadecimal color into RGB
      hex2rgb: function(hex)
      {
          var res = /([0-9A-Z]{2})([0-9A-Z]{2})([0-9A-Z]{2})/.exec(hex);
          if(res==null) // hex is a string : "rgb(xxx,xxx,xxx)    "
          {
             hex = hex.substr(4,hex.length-5);
             res = hex.split(',');
             return [parseInt(res[0], 16), parseInt(res[1], 16), parseInt(res[2], 16)];
          }
          return [parseInt(res[1], 16), parseInt(res[2], 16), parseInt(res[3], 16)];
      },
      // Transforms a RGB color into hexadecimal
      rgb2hex: function(rgb) {
          rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
          function hex(x) {
              return ("0" + parseInt(x).toString(16)).slice(-2);
          }
          return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
      },
      // Performs save
     saveDesign: function(type) {

         if(isUserLogged)
         {
             if(type=="tool-saveas")
             {
                 if($('#stickaz-name form input[name="saveas"]').length == 0    )
                 {
                    $('<input type="hidden" name="saveas" value="true" />').appendTo('#stickaz-name form');
                 }
             }

             if(!hasUsername)
             {
                 $.msgbox('Hey ! To save a design in the community, you must have a username (it will be shown)<br /><br /><label for=\"username\">Username:</label> <input type=\"text\" id=\"username\" name=\"username\" /><br /><br />You will be able to change it later in your options.', {
                       type: "confirm",
                       buttons : [
                         {type: "submit", value: msgSave},
                         {type: "cancel", value: msgCancel}
                       ]
                     }, function(result) {
                        if(result)
                        {
                            $('#username').appendTo('#stickaz-name form').hide();
                            $('#stickaz-name form').submit();
                        }
                });
            }
            else
            {
                $('#stickaz-name form').submit();
            }
        }
        else
        {
            $.msgbox(errorSave, {
                  type: "error",
                  buttons : [ {type: "cancel", value: "Back to the studio"}]
                });
        }
     },
     // Performs publication
     publishDesign: function(currentDesign) {

         if(typeof(currentDesign) == 'undefined')
         {
             currentDesign = false;
         }

         if(!currentData && currentDesign)
         {
             $.msgbox(errorPublish, {
                   type: "error",
                   buttons : [ {type: "cancel", value: "Back to the studio"}]
                 });
         }
         else
         {
             $.msgbox(confirmPublish, {
                   type: "confirm",
                   buttons : [
                     {type: "submit", value: msgContinue},
                     {type: "cancel", value: msgCancel}
                   ]
                 }, function(result) {
                     if(result)
                     {
                         window.location.href = window.location.href  + "&publish=tue";
                     }
                 });
         }
     },
     // Reset the studio
     newDesign: function()
     {
         // we are using a link but we could clear the current studio with an initCanvas+clear title+saveCanvas
     },
     // Redirect on the product page
     orderDesign: function(event)
     {
         if(!currentData)
         {
             event.preventDefault();
             $.msgbox(errorOrder, {
                   type: "error",
                   buttons : [ {type: "cancel", value: "Back to the studio"}]
                 });
         }
     },
};

// Adjusts font size to container length
function manageFontSize(selector,maxLength,defaultSize)
{
    var element = $(selector);
    if(element.val().length > maxLength)
    {
        var palier = 3;
        var px = Math.max(defaultSize - Math.round((element.val().length - maxLength) / palier),10);
        element.css('font-size',px+'px');
    }
    else
    {
        element.css('font-size',defaultSize+'px');
    }
}

// To make :focus work properly
jQuery.extend(jQuery.expr[':'], {
    focus: function(element) {
        return element == document.activeElement;
    }
});
