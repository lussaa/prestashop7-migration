var Studio = {

    tools: { availableColors: null },
    canvas: { element: null, context: null, width: null, height: null },
    model: { matrix: null, rows: null, columns: null },

    gridLinesWidth: 1,
    defaultRows: 16,
    defaultColumns: 16,

    create: function(container, availableColors, model) {
        // Tools
        Studio.tools.availableColors = availableColors;
        // Model
        if (model) {
            Studio.model.rows = model.canvas.length;
            Studio.model.columns = model.canvas[0].length;
            Studio.model.matrix = model.canvas;
        } else {
            Studio.model.rows = Studio.defaultRows;
            Studio.model.columns = Studio.defaultColumns;
            Studio.model.matrix = Studio.createEmptyMatrix(Studio.model.rows, Studio.model.columns);
        }
        // Canvas
        var width = container.width();
        var ratio = Studio.model.rows / Studio.model.columns;
        var height = width * ratio;
        Studio.canvas.element = $('<canvas />');
        Studio.canvas.element.attr('width', width);
        Studio.canvas.element.attr('height', height);
        Studio.canvas.element.appendTo(container);
        Studio.canvas.context = Studio.canvas.element.get(0).getContext('2d');
        Studio.canvas.width = width;
        Studio.canvas.height = height;
        // Draw
        Studio.drawGrid();
        Studio.drawModel();
    },

    createEmptyMatrix: function(rows, columns) {
        var matrix = [];
        for (var i = 0; i < rows; i++)
        {
            matrix.push([]);
            for (var j = 0; j < columns; j++)
            {
                matrix[i][j] = '';
            }
        }
        return matrix;
    },

    getPixelSize: function() {
        return Math.floor((Studio.canvas.width - Studio.gridLinesWidth * Studio.model.columns) / Studio.model.columns);
    },

    drawGrid: function()
    {
        // Verticals
        for (var x = 0; x <= Studio.canvas.width + 1; x += Studio.getPixelSize() + 1)
        {
            Studio.drawLine(x, 0, x, Studio.canvas.height);
        }
        // Horizontals
        for (var y = 0; y <= Studio.canvas.height + 1; y += Studio.getPixelSize() + 1)
        {
            Studio.drawLine(0, y, Studio.canvas.width, y);
        }
    },

    drawModel: function() {
        for (var c = 0; c < Studio.model.columns; c += 1) {
            for (var r = 0; r < Studio.model.rows; r += 1) {
                Studio.drawPixel(r, c);
            }
        }
    },

    drawLine: function(startX, startY, endX, endY, color, lineWidth)
    {
        color = color || '#DFDFDF';
        lineWidth = lineWidth || 1;
        Studio.canvas.context.beginPath();
        Studio.canvas.context.moveTo(startX, startY);
        Studio.canvas.context.lineTo(endX, endY);
        Studio.canvas.context.strokeStyle = color;
        Studio.canvas.context.lineWidth = lineWidth;
        Studio.canvas.context.closePath();
        Studio.canvas.context.stroke();
    },

    drawPixel: function(row, col) {
        var color = Studio.getColor(row, col);
        var pixelSize = Studio.getPixelSize();
        var x = 1 + (col * pixelSize) + col; // TODO adjust for variable spacing
        var y = 1 + (row * pixelSize) + row;
        if (color === null) {
            Studio.canvas.context.clearRect(x, y, pixelSize, pixelSize); // TODO fix erasing the border
//            Studio.canvas.context.fillStyle = 'blue';
//            Studio.canvas.context.fillRect(x, y, pixelSize, pixelSize);
        } else {
            Studio.canvas.context.fillStyle = color;
            Studio.canvas.context.fillRect(x, y, pixelSize, pixelSize);
        }
    },

    getColor: function(row, col) {
        var code = Studio.model.matrix[row][col];
        if (code === '') {
            return null;
        } else {
            return Studio.findColor(code);
        }
    },

    findColor: function(code) {
        for (const colorData of Studio.tools.availableColors) {
            if ('c' + colorData.code === code) {
                return colorData.color;
            }
        }
        return null;
    }

};
