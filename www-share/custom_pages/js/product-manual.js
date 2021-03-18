var Manual2 = {
    
    orderSize : 1.5,
    
    addMark: function() {
        //console.log('add grid !');
    },
    
    adjustGrid: function() {
            
        if (Manual2.size.height > $('#studio-draw').height())
        {
            var ratio = Studio.size.height / $('#studio-draw').height();
            
            $('#studio-draw').css('width', Studio.size.cols * (Studio.getPixelSize() / ratio) + Studio.size.cols);

            Studio.restoreCanvas();
            $('#studio-wrapper').css('height', $('#studio-draw').height());
            $('#canvas_wrapper').css('height', $('#studio-draw').height());
            $('canvas').css('height', $('#studio-draw').height());
        }
        else {
            var diff = $('#studio-draw').height() - Manual2.size.height;
            $('#studio-draw').height('auto');
            $('#studio-draw').css('margin-top', diff/2);
        }
       
        if($('#studio-wrapper').height() < $('#studio-draw').height())
        {
            $('#studio-wrapper').css('margin-top', ($('#studio-draw').height() - $('#studio-wrapper').height()));
        }
    },
    
    
    findCenterPixel: function()
    {
        //find the biggest line
        var maxCount = 0;
        var maxCountRow = 0;
        for (var row=0; row < Studio.size.rows; row++)
        {
            var count = 0;
            for (var col=0; col < Studio.size.cols; col++)
            {
                if(Studio.canvas.data[row][col] != '')
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
        
        if(maxCountRow <= Math.round(Studio.size.rows/10) || maxCountRow >= Math.round(Studio.size.rows/10)*9)
        {
            maxCountRow = Math.round(Studio.size.rows / 2);
        }
        
        //avoid to have the center on an empty pixel
        var centerCol = 0;
        for (var col=Math.floor(Studio.size.cols/2); col < Studio.size.cols; col++)
        {
            if(Studio.canvas.data[maxCountRow][col] != '')
            {
                centerCol = col;
                break;
            }
        }
        
        var half = Studio.getPixelSize() / 2;
        var startX = centerCol * (Studio.getPixelSize() + 1) + half;
        var startY =  maxCountRow * (Studio.getPixelSize() + 1) + half;
        var endX = Studio.size.width - 150;
        var endY = 50;
	    var color = "#00A4C4";

        Studio.canvas.context.lineWidth = 10;
        Studio.canvas.context.fillStyle = color;
        Studio.canvas.context.strokeStyle = '#00A4C4';

        //center line
        Studio.drawLine(0, startY + half+2, Studio.size.width ,  startY + half+2, color, 1);

        //center rect
        Studio.canvas.context.rect(startX-half + 1, startY-half + 1, Studio.getPixelSize() , Studio.getPixelSize());
        //Studio.canvas.context.closePath();
        Studio.canvas.context.stroke();

        
    },    
    
    initControls: function() {

        $('ul#color-list li span').each(function() {

            if($(this).html() == "--")
            {
                $(this).parent().remove();
            }

        });
        
        var width = $('ul#color-list li').length * ($('ul#color-list li').width() + 4);
        
        $('ul#color-list').css('width', width);
        $('div#studio-header').css('width', width);

        $('div#studio-header').css('left', ($('#studio-draw').width() - width) / 2);
        
    },
    initUpload: function() {},
    
    // This function has duplicated code from updateCounts
    setInfo: function() {
        
        var totalKaz = 0;
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

        
        $('span#total-stickers').html(totalKaz);        

            $('#draw-width').html(Studio.kazSize.width * Manual2.orderSize);
            $('#draw-height').html(Studio.kazSize.height * Manual2.orderSize);
    },

    //helping out with shipping quantities, used by studioshipp-manual.tpl and StickazStudioSHIPPController
    shippingHelpInfo: function() {

        var totalKaz = 0;
        $('table#color-list tr.colorline').each(function() {
            idColor = $(this).attr('id');
            nbColor = isNaN(Studio.counts[idColor]) ? 0 : Studio.counts[idColor];
            if(nbColor == 0)
            {
                $(this).remove();

            }
            else
            {
                $(this).find('td.colorcode').html("&nbsp;&nbsp;&nbsp;"+idColor);
                $(this).find('td.colorcount').html("&nbsp;&nbsp;&nbsp;"+nbColor);
                var packagescount9 = (nbColor*1.1/9);         //1.1 is to get plus extra 10%
                $(this).find('td.packagescount9').html("&nbsp;&nbsp;&nbsp;"+packagescount9.toFixed(0));
                var extrakaz9=(packagescount9%1)*9;
                $(this).find('td.extrakaz9').html("&nbsp;&nbsp;&nbsp;"+extrakaz9.toFixed(0));

                var packagescount4 = (nbColor*1.1/4);
                $(this).find('td.packagescount4').html("&nbsp;&nbsp;&nbsp;"+packagescount4.toFixed(0));
                var extrakaz4=(packagescount4%1)*4;
                $(this).find('td.extrakaz4').html("&nbsp;&nbsp;&nbsp;"+extrakaz4);

            }

            totalKaz += nbColor;

        });


    },
    
};

Manual2 = $.extend({}, Studio, Manual2);

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